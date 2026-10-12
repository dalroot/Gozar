<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$settings = \App\Models\Setting::all()->pluck('value', 'key');
$pasargad = new \App\Services\PasargadService(
    $settings->get('pasargad_host'),
    $settings->get('pasargad_sudo_username'),
    $settings->get('pasargad_sudo_password'),
    $settings->get('pasargad_node_hostname')
);

$trialCounter = (int) (\App\Models\Setting::where('key', 'trial_counter')->value('value') ?? 0);
$foundUsername = null;
while (true) {
    $trialCounter++;
    $candidate = "test" . $trialCounter;
    $dbExists = \App\Models\Order::where('panel_username', $candidate)->exists();
    if ($dbExists) {
        continue;
    }
    $panelUser = $pasargad->getUser($candidate);
    if ($panelUser && !empty($panelUser['username'])) {
        continue;
    }
    $foundUsername = $candidate;
    break;
}

echo "Selected unique username: " . $foundUsername . "\n";

$volumeMB = (int) $settings->get('trial_volume_mb', 3072);
$durationHours = (int) $settings->get('trial_duration_hours', 24);
$expiresAt = now()->addHours($durationHours);
$dataLimitBytes = $volumeMB * 1024 * 1024;

$res = $pasargad->createUser([
    'username' => $foundUsername,
    'expire' => $expiresAt->timestamp,
    'data_limit' => $dataLimitBytes,
    'group_ids' => [1],
]);

if (!$res || empty($res['subscription_url'])) {
    echo "ERROR_CREATING_USER: " . json_encode($res) . "\n";
    exit(1);
}

$subUrl = $res['subscription_url'];

$user = \App\Models\User::find(53);
if ($user) {
    $trialPlan = \App\Models\Plan::firstOrCreate(
        ['name' => 'سرویس هدیه / تست رایگان'],
        ['price' => 0, 'volume_gb' => 3, 'duration_days' => 1, 'features' => 'اکانت هدیه روزنه', 'is_active' => false]
    );

    \App\Models\Order::create([
        'user_id' => $user->id,
        'plan_id' => $trialPlan->id,
        'status' => 'paid',
        'expires_at' => $expiresAt,
        'payment_method' => 'trial',
        'config_details' => $subUrl,
        'amount' => 0,
        'source' => 'telegram',
        'panel_username' => $foundUsername,
    ]);

    $user->increment('trial_accounts_taken');
}

\App\Models\Setting::updateOrCreate(['key' => 'trial_counter'], ['value' => (string) $trialCounter]);

$configs = '';
try {
    $subRes = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])->get($subUrl);
    $body = $subRes->body();
    $decoded = base64_decode($body, true);
    $configs = $decoded ?: $body;
} catch (\Exception $e) {
    $configs = 'Fetch error: ' . $e->getMessage();
}

$output = [
    'user_id' => $user?->id,
    'name' => $user?->name,
    'chat_id' => $user?->telegram_chat_id,
    'username' => $foundUsername,
    'volume_mb' => $volumeMB,
    'duration_hours' => $durationHours,
    'sub_url' => $subUrl,
    'configs' => $configs
];

echo "RESULT_JSON_START\n" . json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\nRESULT_JSON_END\n";
