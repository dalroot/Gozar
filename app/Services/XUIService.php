<?php

namespace App\Services;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XUIService
{
    protected string $baseUrl;
    protected string $basePath;
    protected string $username;
    protected string $password;
    protected CookieJar $cookieJar;
    protected bool $isLoggedIn = false;
    protected string $csrfToken = '';

    public function __construct(?string $host, ?string $username, ?string $password)
    {
        $clean = function ($val) {
            if (is_null($val)) return '';
            $str = (string) $val;
            $decoded = json_decode($str, true);
            if (is_string($decoded)) {
                $str = $decoded;
            }
            return trim($str, "\"'\t\n\r ");
        };

        $host = $clean($host);
        $parsedUrl = parse_url(rtrim($host, '/'));
        $this->baseUrl = ($parsedUrl['scheme'] ?? 'http') . '://' . ($parsedUrl['host'] ?? '') . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');
        $this->basePath = $parsedUrl['path'] ?? '';

        if (!empty($this->basePath) && !str_starts_with($this->basePath, '/')) {
            $this->basePath = '/' . $this->basePath;
        }

        $this->username = $clean($username);
        $this->password = $clean($password);
        $this->cookieJar = new CookieJar();
    }

    private function getClient(): PendingRequest
    {
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        if (!empty($this->csrfToken)) {
            $headers['X-CSRF-Token'] = $this->csrfToken;
        }

        return Http::withOptions([
            'cookies' => $this->cookieJar,
            'verify' => false,
            'timeout' => 120,
            'connect_timeout' => 60,
        ])->withHeaders($headers)->withoutVerifying();
    }

    public function getClients(int $inboundId): array
    {
        if (!$this->login()) {
            Log::error('Cannot get clients: Login failed');
            return [];
        }

        try {
            $url = $this->baseUrl . $this->basePath . "/panel/api/inbounds/get/{$inboundId}";
            $response = $this->getClient()->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch inbound details', [
                    'inbound_id' => $inboundId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();

            Log::debug('X-UI raw response for getClients', [
                'inbound_id' => $inboundId,
                'full_response' => $data
            ]);

            $rawSettings = $data['obj']['settings'] ?? '{}';
            $settings = is_array($rawSettings) ? $rawSettings : json_decode($rawSettings, true);
            $clients = $settings['clients'] ?? [];

            Log::info('Successfully fetched clients', [
                'inbound_id' => $inboundId,
                'count' => count($clients),
                'clients_list' => array_map(function($c) {
                    return ['id' => $c['id'] ?? null, 'email' => $c['email'] ?? null, 'subId' => $c['subId'] ?? null];
                }, $clients)
            ]);

            return $clients;

        } catch (\Throwable $e) {
            Log::error('Exception while fetching clients', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function login(): bool
    {
        if ($this->isLoggedIn) {
            return true;
        }

        try {
            // Step 1: GET login page to establish session cookie and extract CSRF token
            $mainUrl = $this->baseUrl . $this->basePath . '/';
            $getResponse = $this->getClient()->get($mainUrl);

            // Step 2: Extract CSRF token from <meta name="csrf-token" content="...">
            if (preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']*)["\']/', $getResponse->body(), $matches)) {
                $this->csrfToken = $matches[1];
            }

            Log::debug('XUI Login: pre-flight completed', [
                'url' => $mainUrl,
                'get_status' => $getResponse->status(),
                'csrf_found' => !empty($this->csrfToken),
            ]);

            // Step 3: POST login with CSRF token header (if available)
            $loginApiUrl = $this->baseUrl . $this->basePath . '/login';
            $loginData = [
                'username' => $this->username,
                'password' => $this->password,
            ];

            // Primary attempt: form-encoded POST
            $response = $this->getClient()
                ->asForm()
                ->post($loginApiUrl, $loginData);

            // Fallback: if form POST returns 403 and CSRF exists, retry with JSON body
            if ($response->status() === 403 && !empty($this->csrfToken)) {
                Log::debug('XUI Login: form POST returned 403, retrying with JSON body');
                $response = $this->getClient()->post($loginApiUrl, $loginData);
            }

            $responseBody = $response->body();
            $isSuccess = $response->successful() && (
                    $response->json('success') === true ||
                    Str::contains($responseBody, 'Login successful') ||
                    Str::contains($responseBody, 'success') ||
                    $response->redirect()
                );

            if ($isSuccess) {
                Log::info('XUI Login successful');
                $this->isLoggedIn = true;
                return true;
            } else {
                Log::error('XUI Login Failed', [
                    'url' => $loginApiUrl,
                    'status' => $response->status(),
                    'body' => $responseBody,
                    'json' => $response->json(),
                    'csrf_sent' => !empty($csrfToken),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('XUI Connection Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function getInbounds(): array
    {
        if (!$this->login()) {
            Log::error('Cannot get inbounds: Login failed');
            return [];
        }

        try {
            $url = $this->baseUrl . $this->basePath . '/panel/api/inbounds/list';
            $response = $this->getClient()
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->get($url);

            if (!$response->successful()) {
                Log::error('Failed to fetch inbounds', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [];
            }

            $data = $response->json();
            $inbounds = $data['obj'] ?? [];
            Log::info('Successfully fetched inbounds', ['count' => count($inbounds)]);
            return $inbounds;

        } catch (\Exception $e) {
            Log::error('Exception while fetching inbounds', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    public function getClientByEmail(string $email): ?array
    {
        if (!$this->login()) {
            return null;
        }

        try {
            $cleanBasePath = rtrim($this->basePath, '/');
            $url = $this->baseUrl . $cleanBasePath . '/panel/api/clients/get/' . rawurlencode($email);
            $response = $this->getClient()->get($url);

            if ($response->successful() && ($response->json('success') === true)) {
                $obj = $response->json('obj');
                return $obj['client'] ?? $obj;
            }
            return null;
        } catch (\Throwable $e) {
            Log::warning('Error fetching client by email: ' . $e->getMessage(), ['email' => $email]);
            return null;
        }
    }

    public function updateClientByEmail(string $email, array $clientData): ?array
    {
        if (!$this->login()) {
            return ['success' => false, 'msg' => 'Authentication failed.'];
        }

        try {
            $cleanBasePath = rtrim($this->basePath, '/');
            $url = $this->baseUrl . $cleanBasePath . '/panel/api/clients/update/' . rawurlencode($email);

            $payload = [
                'id' => $clientData['id'] ?? $clientData['uuid'] ?? Str::uuid()->toString(),
                'email' => $email,
                'totalGB' => $clientData['total'] ?? $clientData['totalGB'] ?? 0,
                'expiryTime' => $clientData['expiryTime'] ?? 0,
                'enable' => $clientData['enable'] ?? true,
                'subId' => $clientData['subId'] ?? Str::random(16),
                'tgId' => $clientData['tgId'] ?? 0,
                'limitIp' => $clientData['limitIp'] ?? 0,
                'flow' => $clientData['flow'] ?? '',
            ];

            Log::info('Updating XUI client by email', ['email' => $email, 'url' => $url]);
            $response = $this->getClient()->asJson()->post($url, $payload);

            return $response->json() ?? ['success' => $response->successful()];
        } catch (\Throwable $e) {
            Log::error('Exception in updateClientByEmail', ['email' => $email, 'error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function attachClient(string $email, array $inboundIds): ?array
    {
        if (!$this->login() || empty($inboundIds)) {
            return ['success' => false, 'msg' => 'Invalid parameters or auth failed.'];
        }

        try {
            $cleanBasePath = rtrim($this->basePath, '/');
            $url = $this->baseUrl . $cleanBasePath . '/panel/api/clients/' . rawurlencode($email) . '/attach';

            $payload = [
                'inboundIds' => array_values(array_unique(array_map('intval', $inboundIds)))
            ];

            Log::info('Attaching XUI client to inbounds', ['email' => $email, 'inbound_ids' => $payload['inboundIds']]);
            $response = $this->getClient()->asJson()->post($url, $payload);

            return $response->json() ?? ['success' => $response->successful()];
        } catch (\Throwable $e) {
            Log::error('Exception in attachClient', ['email' => $email, 'error' => $e->getMessage()]);
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }

    public function resetClientTrafficByEmail(string $email): bool
    {
        if (!$this->login()) {
            return false;
        }

        try {
            $cleanBasePath = rtrim($this->basePath, '/');
            $url = $this->baseUrl . $cleanBasePath . '/panel/api/clients/resetTraffic/' . rawurlencode($email);
            $response = $this->getClient()->post($url);

            return $response->successful() && ($response->json('success') ?? false);
        } catch (\Throwable $e) {
            Log::warning('Error resetting traffic by email', ['email' => $email, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function addClient($inboundId, array $clientData): ?array
    {
        if (!$this->login()) {
            return ['success' => false, 'msg' => 'Authentication to X-UI panel failed.'];
        }

        try {
            $inboundIds = is_array($inboundId) ? array_map('intval', $inboundId) : [(int) $inboundId];
            $inboundIds = array_values(array_unique(array_filter($inboundIds)));
            $primaryInboundId = $inboundIds[0] ?? 0;

            $email = $clientData['email'] ?? ('client_' . rand(1000, 9999));
            $totalGB = $clientData['total'] ?? $clientData['totalGB'] ?? 0;
            $expiryTime = $clientData['expiryTime'] ?? 0;

            // بررسی کلاینت موجود در پنل مدرن
            $existing = $this->getClientByEmail($email);
            if ($existing) {
                Log::info('Client already exists in XUI, updating and attaching inbounds', ['email' => $email]);
                $uuid = $existing['uuid'] ?? $existing['id'] ?? ($clientData['id'] ?? Str::uuid()->toString());
                $subId = $existing['subId'] ?? ($clientData['subId'] ?? Str::random(16));

                $updateData = array_merge($clientData, [
                    'id' => $uuid,
                    'subId' => $subId,
                    'total' => $totalGB,
                    'expiryTime' => $expiryTime,
                ]);

                $this->updateClientByEmail($email, $updateData);
                $this->attachClient($email, $inboundIds);
                $this->resetClientTrafficByEmail($email);

                return [
                    'success' => true,
                    'msg' => 'Client updated and attached.',
                    'generated_uuid' => $uuid,
                    'generated_subId' => $subId,
                    'inbound_id' => $primaryInboundId,
                    'inbound_ids' => $inboundIds
                ];
            }

            $uuid = $clientData['id'] ?? $clientData['uuid'] ?? Str::uuid()->toString();
            $subId = $clientData['subId'] ?? Str::random(16);

            Log::info('Creating XUI client', [
                'inbound_ids' => $inboundIds,
                'email' => $email,
                'generated_uuid' => $uuid,
                'generated_subId' => $subId
            ]);

            $cleanBasePath = rtrim($this->basePath, '/');

            // Attempt 1: Modern 3x-ui v3.6+ API endpoint (/panel/api/clients/add)
            $modernUrl = $this->baseUrl . $cleanBasePath . '/panel/api/clients/add';
            $modernPayload = [
                'inboundIds' => $inboundIds,
                'client' => [
                    'id' => $uuid,
                    'email' => $email,
                    'totalGB' => $totalGB,
                    'expiryTime' => $expiryTime,
                    'enable' => true,
                    'subId' => $subId,
                    'tgId' => 0,
                    'limitIp' => 0,
                    'flow' => '',
                ]
            ];

            Log::info('Trying modern XUI clients/add endpoint', ['url' => $modernUrl, 'inbound_ids' => $inboundIds]);
            $response = $this->getClient()->asJson()->post($modernUrl, $modernPayload);

            if ($response->status() === 200 && ($response->json('success') === true || Str::contains($response->body(), 'success'))) {
                Log::info('Modern XUI clients/add successful');
                return array_merge($response->json() ?? ['success' => true], [
                    'generated_uuid' => $uuid,
                    'generated_subId' => $subId,
                    'inbound_id' => $primaryInboundId,
                    'inbound_ids' => $inboundIds
                ]);
            }

            $respBody = $response->body();
            if (Str::contains($respBody, 'already in use') || Str::contains($respBody, 'already exists')) {
                Log::info('Client email already in use during add, switching to update/attach', ['email' => $email]);
                $this->updateClientByEmail($email, [
                    'id' => $uuid,
                    'subId' => $subId,
                    'total' => $totalGB,
                    'expiryTime' => $expiryTime,
                ]);
                $this->attachClient($email, $inboundIds);
                $this->resetClientTrafficByEmail($email);

                return [
                    'success' => true,
                    'msg' => 'Client updated and attached.',
                    'generated_uuid' => $uuid,
                    'generated_subId' => $subId,
                    'inbound_id' => $primaryInboundId,
                    'inbound_ids' => $inboundIds
                ];
            }

            Log::warning('Modern XUI clients/add returned non-success, attempting legacy endpoints', [
                'status' => $response->status(),
                'body' => $respBody
            ]);

            // Attempt 2: Legacy 3x-ui / 3x-ui v2.x form endpoints for each inbound
            $clientSettings = [
                'id' => $uuid,
                'email' => $email,
                'totalGB' => $totalGB,
                'expiryTime' => $expiryTime,
                'enable' => true,
                'tgId' => '',
                'subId' => $subId,
                'limitIp' => 0,
                'flow' => '',
            ];
            $settingsJson = json_encode(['clients' => [$clientSettings]]);

            $legacyEndpoints = [
                $cleanBasePath . "/panel/api/inbounds/addClient",
                $cleanBasePath . "/panel/inbound/addClient",
                $cleanBasePath . "/xui/inbound/addClient"
            ];

            $lastError = $response->json('msg') ?? $response->body();
            $lastResponse = $response;
            $successCount = 0;

            foreach ($inboundIds as $inbId) {
                foreach ($legacyEndpoints as $endpoint) {
                    $addClientUrl = $this->baseUrl . $endpoint;
                    $currentResponse = $this->getClient()->asForm()->post($addClientUrl, [
                        'id' => $inbId,
                        'settings' => $settingsJson,
                    ]);

                    $lastResponse = $currentResponse;
                    $responseData = $currentResponse->json();

                    if ($currentResponse->status() === 200 && isset($responseData['success']) && $responseData['success'] === true) {
                        $successCount++;
                        break;
                    } else {
                        $lastError = $responseData['msg'] ?? $currentResponse->body();
                    }
                }
            }

            if ($successCount > 0) {
                return [
                    'success' => true,
                    'generated_uuid' => $uuid,
                    'generated_subId' => $subId,
                    'inbound_id' => $primaryInboundId,
                    'inbound_ids' => $inboundIds
                ];
            }

            $errorMsg = "All addClient endpoints failed. Last error: " . ($lastError ?: 'Unknown error');
            Log::error('XUI addClient failed completely', [
                'inbound_ids' => $inboundIds,
                'last_error' => $lastError,
                'last_response_body' => $lastResponse?->body()
            ]);
            return ['success' => false, 'msg' => $errorMsg];

        } catch (\Throwable $e) {
            Log::error('Exception in XUI addClient', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'msg' => 'Error creating client: ' . $e->getMessage()];
        }
    }

    public function resetClientTraffic(int $inboundId, string $email): bool
    {
        if (!$this->login()) {
            Log::error('Cannot reset traffic: Login failed');
            return false;
        }

        try {
            // ✅ FIX: ساختار URL طبق داکیومنت رسمی 3x-ui
            // POST /panel/api/inbounds/{inboundId}/resetClientTraffic/{email}
            $url = $this->baseUrl . $this->basePath . "/panel/api/inbounds/{$inboundId}/resetClientTraffic/" . rawurlencode($email);

            Log::info('Resetting XUI client traffic', [
                'url' => $url,
                'inbound_id' => $inboundId,
                'email' => $email
            ]);

            $response = $this->getClient()->post($url);

            if ($response->successful() && $response->json('success')) {
                Log::info('✅ Client traffic reset successfully', ['email' => $email]);
                return true;
            } else {
                Log::error('❌ Failed to reset client traffic', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'inbound_id' => $inboundId,
                    'email' => $email
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Exception in resetClientTraffic', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'email' => $email,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    public function updateClient(int $inboundId, string $clientId, array $clientData): ?array
    {
        if (!$this->login()) {
            return ['success' => false, 'msg' => 'Authentication failed.'];
        }

        try {
            $subId = $clientData['subId'] ?? Str::random(16);

            $clientSettings = [
                'id' => $clientId,
                'email' => $clientData['email'],
                'totalGB' => $clientData['total'] ?? 0,
                'expiryTime' => $clientData['expiryTime'] ?? 0,
                'enable' => true,
                'tgId' => '',
                'subId' => $subId,
                'limitIp' => 0,
                'flow' => '',
            ];

            $settings = json_encode(['clients' => [$clientSettings]]);

            $updateClientUrl = $this->baseUrl . $this->basePath . "/panel/api/inbounds/updateClient/{$clientId}";

            Log::info('Updating XUI client', [
                'url' => $updateClientUrl,
                'inbound_id' => $inboundId,
                'client_id' => $clientId
            ]);

            $response = $this->getClient()->asForm()->post($updateClientUrl, [
                'id' => $inboundId,
                'settings' => $settings,
            ]);

            $responseData = $response->json();

            Log::info('XUI updateClient response', [
                'status' => $response->status(),
                'success' => $responseData['success'] ?? false,
                'msg' => $responseData['msg'] ?? 'N/A'
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('Exception in XUI updateClient', [
                'message' => $e->getMessage(),
                'inbound_id' => $inboundId,
                'client_id' => $clientId,
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'msg' => 'Error updating client: ' . $e->getMessage()];
        }
    }
}
