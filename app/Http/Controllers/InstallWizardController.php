<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstallWizardController extends Controller
{
    protected string $lockFile;
    protected string $tokenFile;

    public function __construct()
    {
        $this->lockFile = storage_path('installed');
        $this->tokenFile = storage_path('setup_token');
    }

    protected function isInstalled(): bool
    {
        return file_exists($this->lockFile);
    }

    protected function verifyToken(Request $request): bool
    {
        if (!file_exists($this->tokenFile)) {
            return false;
        }

        $validToken = trim(file_get_contents($this->tokenFile));
        $providedToken = $request->query('token') ?? $request->input('token');

        return !empty($validToken) && hash_equals($validToken, (string)$providedToken);
    }

    public function show(Request $request)
    {
        if ($this->isInstalled()) {
            return redirect('/admin');
        }

        if (!$this->verifyToken($request)) {
            abort(403, 'Unauthorized Setup Token. Check server console for valid setup URL.');
        }

        $token = $request->query('token');
        return view('setup.wizard', compact('token'));
    }

    public function checkDns(Request $request)
    {
        if ($this->isInstalled() || !$this->verifyToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $domain = trim($request->input('domain', ''));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        if (empty($domain)) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid domain.']);
        }

        $dnsRecords = @dns_get_record($domain, DNS_A);
        $serverIp = @file_get_contents('https://api.ipify.org') ?: $request->server('SERVER_ADDR');

        if (empty($dnsRecords)) {
            return response()->json([
                'success' => false,
                'message' => "No DNS A-record found for {$domain}. Make sure DNS has propagated.",
                'server_ip' => $serverIp
            ]);
        }

        $matched = false;
        $resolvedIps = [];
        foreach ($dnsRecords as $rec) {
            $ip = $rec['ip'] ?? '';
            $resolvedIps[] = $ip;
            if ($ip === $serverIp) {
                $matched = true;
                break;
            }
        }

        return response()->json([
            'success' => true,
            'matched' => $matched,
            'server_ip' => $serverIp,
            'resolved_ips' => $resolvedIps,
            'message' => $matched
                ? "✅ DNS points correctly to this server ({$serverIp})."
                : "⚠️ {$domain} resolves to " . implode(', ', $resolvedIps) . " but server IP is {$serverIp}. SSL may fail if not routed correctly."
        ]);
    }

    public function checkBot(Request $request)
    {
        if ($this->isInstalled() || !$this->verifyToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $token = trim($request->input('bot_token', ''));
        if (empty($token)) {
            return response()->json(['success' => false, 'message' => 'Token cannot be empty.']);
        }

        try {
            $response = Http::timeout(6)->get("https://api.telegram.org/bot{$token}/getMe");
            if ($response->successful() && $response->json('ok')) {
                $bot = $response->json('result');
                return response()->json([
                    'success' => true,
                    'bot_name' => $bot['first_name'] ?? 'Bot',
                    'username' => '@' . ($bot['username'] ?? ''),
                    'message' => "Connected to " . ($bot['first_name'] ?? '') . " (@" . ($bot['username'] ?? '') . ")"
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Setup Bot check failed: ' . $e->getMessage());
        }

        return response()->json(['success' => false, 'message' => 'Invalid bot token or cannot connect to Telegram API.']);
    }

    public function process(Request $request)
    {
        if ($this->isInstalled() || !$this->verifyToken($request)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'domain' => 'required|string',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:6',
            'bot_token' => 'nullable|string',
            'panel_type' => 'nullable|string'
        ]);

        $domain = trim($request->input('domain'));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = rtrim($domain, '/');

        // Update or create Admin User
        $admin = User::firstOrNew(['email' => $request->input('admin_email')]);
        $admin->name = 'System Administrator';
        $admin->email = $request->input('admin_email');
        $admin->password = Hash::make($request->input('admin_password'));
        $admin->is_admin = true;
        $admin->save();

        // Save Settings
        $settings = [
            'app_url' => "https://{$domain}",
            'panel_type' => $request->input('panel_type', 'marzban'),
        ];

        if ($request->filled('bot_token')) {
            $settings['telegram_bot_token'] = trim($request->input('bot_token'));
        }

        foreach ($settings as $key => $val) {
            Setting::updateOrCreate(['key' => $key], ['value' => $val]);
        }

        // Update .env file
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $env = file_get_contents($envPath);
            $env = preg_replace('/^APP_URL=.*$/m', "APP_URL=https://{$domain}", $env);
            if ($request->filled('bot_token')) {
                $botToken = trim($request->input('bot_token'));
                if (preg_match('/^TELEGRAM_BOT_TOKEN=/m', $env)) {
                    $env = preg_replace('/^TELEGRAM_BOT_TOKEN=.*$/m', "TELEGRAM_BOT_TOKEN={$botToken}", $env);
                } else {
                    $env .= "\nTELEGRAM_BOT_TOKEN={$botToken}";
                }
            }
            file_put_contents($envPath, $env);
        }

        // Trigger SSL & Nginx generation via helper script
        $cmd = "sudo bash /var/www/gozar/setup_finalize.sh " . escapeshellarg($domain) . " " . escapeshellarg($request->input('admin_email')) . " > /tmp/gozar_ssl.log 2>&1 &";
        exec($cmd);

        // Mark as installed
        file_put_contents($this->lockFile, date('Y-m-d H:i:s'));
        @unlink($this->tokenFile);

        return response()->json([
            'success' => true,
            'redirect_url' => "https://{$domain}/admin"
        ]);
    }
}
