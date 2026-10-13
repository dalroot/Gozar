<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس ارتباط با پنل پاسارگاد (PasarGuard)
 * 
 * این سرویس برای مدیریت کاربران در پنل پاسارگاد استفاده می‌شود.
 * API پاسارگاد مشابه Marzban است با تفاوت‌های جزئی.
 */
class PasargadService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?string $nodeHostname;
    protected ?string $accessToken = null;
    protected ?string $apiKey = null;

    public function __construct(?string $baseUrl, ?string $username, ?string $password, ?string $nodeHostname = null, ?string $apiKey = null)
    {
        // پاکسازی آدرس از کوتیشن‌ها و فاصله‌های احتمالی و اسلش انتها
        $this->baseUrl = rtrim(trim((string)$baseUrl, " '\"\t\n\r\0\x0B"), '/');
        $this->username = trim((string)$username);
        $this->password = trim((string)$password);
        $this->nodeHostname = $nodeHostname ? trim((string)$nodeHostname) : null;
        $this->apiKey = $apiKey ? trim((string)$apiKey) : (string) (\App\Models\Setting::where('key', 'pasargad_api_key')->value('value') ?: 'pg_key_0349b929-0f74-41b2-a21c-b842e16d6f19');
    }

    /**
     * ایجاد کلاینت HTTP با هدر استاندارد API Key یا Bearer Token
     */
    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::withOptions(['verify' => false])->withHeaders(['Accept' => 'application/json']);

        if (!empty($this->apiKey)) {
            return $client->withHeaders(['X-API-Key' => $this->apiKey]);
        }

        if (!$this->accessToken) {
            $this->login();
        }

        return $client->withToken($this->accessToken);
    }

    /**
     * لاگین به پنل و دریافت توکن JWT
     */
    public function login(): bool
    {
        try {
            // پاسارگاد از Form Data برای لاگین استفاده می‌کند (مثل Marzban)
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post($this->baseUrl . '/api/admin/token', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // استخراج توکن - پاسارگاد ممکن است در ساختارهای مختلف برگرداند
                $token = $data['access_token'] ?? null;
                if (!$token && isset($data['data'])) {
                    $token = $data['data']['token'] ?? $data['data']['access_token'] ?? null;
                }

                if ($token) {
                    $this->accessToken = $token;
                    Log::info('Pasargad Login Successful');
                    return true;
                }
            }

            Log::error('Pasargad Login Failed:', ['status' => $response->status(), 'body' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('Pasargad Login Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ساخت کاربر جدید در پنل
     */
    public function createUser(array $userData): ?array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) {
                return ['detail' => 'Authentication failed'];
            }
        }

        try {
            // ساختار درخواست برای پاسارگاد
            $payload = [
                'username' => $userData['username'],
                'proxies' => [
                    'vmess' => new \stdClass(),
                    'vless' => ['flow' => ''],
                    'trojan' => new \stdClass(),
                    'shadowsocks' => ['method' => 'chacha20-ietf-poly1305'],
                ],
                'inbounds' => new \stdClass(),
                'expire' => $userData['expire'] ?? 0,
                'data_limit' => $userData['data_limit'] ?? 0,
                'data_limit_reset_strategy' => 'no_reset',
                'status' => 'active',
                'note' => $userData['note'] ?? 'Created by VPNMarket',
                // 🔥 نکته مهم: پاسارگاد نیاز به group_ids دارد
                'group_ids' => $userData['group_ids'] ?? [1],
            ];

            $response = $this->request()->post($this->baseUrl . '/api/user', $payload);

            $result = $response->json();
            
            Log::info('Pasargad Create User Response:', $result ?? ['raw' => $response->body()]);

            if ($response->successful() && isset($result['username'])) {
                // اگر API خودش subscription_url برگردونده، فقط پورت رو از اون حذف کن
                if (!empty($result['subscription_url'])) {
                    $apiSubUrl = $result['subscription_url'];
                    
                    // چک کنیم که آیا لینک کامل است (با http شروع میشه) یا فقط path
                    if (str_starts_with($apiSubUrl, 'http')) {
                        // لینک کامل است، فقط پورت رو حذف کن
                        $parsed = parse_url($apiSubUrl);
                        $scheme = $parsed['scheme'] ?? 'https';
                        $host = $parsed['host'] ?? '';
                        $path = $parsed['path'] ?? '';
                        $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';
                        
                        $result['subscription_url'] = "{$scheme}://{$host}{$path}{$query}";
                    } else {
                        // فقط path است (مثل /sub/...) - باید baseUrl یا nodeHostname رو اضافه کنیم
                        $parsed = parse_url($this->baseUrl);
                        $scheme = $parsed['scheme'] ?? 'https';
                        
                        // تهیه هاست بدون پروتکل
                        $host = $this->nodeHostname ?: ($parsed['host'] ?? '');
                        if (str_contains($host, '://')) {
                            $host = parse_url($host, PHP_URL_HOST);
                        }
                        
                        // حذف پورت از host اگر موجود بود
                        $host = preg_replace('/:\d+/', '', $host);
                        
                        $result['subscription_url'] = "{$scheme}://{$host}{$apiSubUrl}";
                    }
                    
                    Log::info('Pasargad: Used API subscription_url and cleaned port', [
                        'original' => $apiSubUrl,
                        'cleaned' => $result['subscription_url']
                    ]);
                } else {
                    // در صورتی که API لینک سابسکریپشن نمی‌ده، خودمون می‌سازیم
                    $result['subscription_url'] = $this->generateSubscriptionLink($result['username']);
                }
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Pasargad Create User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * آپدیت اطلاعات کاربر
     */
    public function updateUser(string $username, array $userData): ?array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return null;
        }

        try {
            $payload = [];
            
            if (isset($userData['expire'])) {
                $payload['expire'] = $userData['expire'];
            }
            if (isset($userData['data_limit'])) {
                $payload['data_limit'] = $userData['data_limit'];
            }
            if (isset($userData['status'])) {
                $payload['status'] = $userData['status'];
            }

            $response = $this->request()->put($this->baseUrl . "/api/user/{$username}", $payload);

            Log::info('Pasargad Update User Response:', $response->json() ?? ['raw' => $response->body()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('Pasargad Update User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * حذف کاربر
     */
    public function deleteUser(string $username): bool
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return false;
        }

        try {
            $response = $this->request()->delete($this->baseUrl . "/api/user/{$username}");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Pasargad Delete User Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * دریافت اطلاعات کاربر
     */
    public function getUser(string $username): ?array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return null;
        }

        try {
            $response = $this->request()->get($this->baseUrl . "/api/user/{$username}");

            if ($response->successful()) {
                return $response->json();
            }
            return null;

        } catch (\Exception $e) {
            Log::error('Pasargad Get User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ریست کردن ترافیک مصرفی کاربر
     */
    public function resetUserTraffic(string $username): bool
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return false;
        }

        try {
            $response = $this->request()->post($this->baseUrl . "/api/user/{$username}/reset");

            Log::info('Pasargad Reset Traffic Response:', ['username' => $username, 'status' => $response->status()]);
            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Pasargad Reset Traffic Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * دریافت لیست گروه‌ها
     */
    public function getGroups(): array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return [];
        }

        try {
            $response = $this->request()->get($this->baseUrl . '/api/groups');

            if ($response->successful()) {
                $data = $response->json();
                // پاسارگاد گروه‌ها را در کلید groups برمی‌گرداند
                return $data['groups'] ?? $data['data'] ?? (is_array($data) ? $data : []);
            }
            return [];

        } catch (\Exception $e) {
            Log::error('Pasargad Get Groups Exception:', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ساخت لینک سابسکریپشن
     */
    public function generateSubscriptionLink(string $username): string
    {
        $baseUrl = $this->baseUrl;
        $nodeHostname = $this->nodeHostname ? trim($this->nodeHostname) : null;
        
        Log::debug('Pasargad generating sub link', [
            'base_url' => $baseUrl,
            'node_hostname' => $nodeHostname,
            'username' => $username
        ]);

        // استخراج بخش دامنه از nodeHostname یا baseUrl
        // اگر nodeHostname کامل بود (با http)، از آن استفاده می‌کنیم، وگرنه از baseUrl برای پروتکل کمک می‌گیریم
        $targetUrl = ($nodeHostname && str_starts_with($nodeHostname, 'http')) ? $nodeHostname : $baseUrl;
        $parsed = parse_url($targetUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        
        // اگر nodeHostname پروتکل نداشت اما مقدار داشت، از خودش استفاده کن، وگرنه از hostِ baseUrl
        if ($nodeHostname && !str_starts_with($nodeHostname, 'http')) {
            $host = $nodeHostname;
        } else {
            $host = $parsed['host'] ?? '';
        }

        // 🔥 فیلتر نهایی: حذف پورت از دامنه در هر وضعیتی
        $cleanHost = preg_replace('/:\d+/', '', $host);
        
        $finalLink = "{$scheme}://{$cleanHost}/sub/{$username}";
        
        Log::info('Pasargad final sub link generated:', [
            'link' => $finalLink,
            'input_node' => $nodeHostname
        ]);
        
        return $finalLink;
    }

    /**
     * دریافت لیست نودها
     */
    public function getNodes(): array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return [];
        }

        try {
            $response = $this->request()->get($this->baseUrl . '/api/nodes');

            if ($response->successful()) {
                $data = $response->json();
                return $data['nodes'] ?? (is_array($data) ? $data : []);
            }
            return [];

        } catch (\Exception $e) {
            Log::error('Pasargad Get Nodes Exception:', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * آمار سیستم
     */
    public function getSystemStats(): ?array
    {
        if (!$this->accessToken && empty($this->apiKey)) {
            if (!$this->login()) return null;
        }

        try {
            $response = $this->request()->get($this->baseUrl . '/api/system');

            if ($response->successful()) {
                return $response->json();
            }
            return null;

        } catch (\Exception $e) {
            Log::error('Pasargad System Stats Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }
}

