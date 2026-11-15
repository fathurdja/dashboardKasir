<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $credentialsPath;

    public function __construct()
    {
        $defaultPath = storage_path('app/dashboardkasir-firebase-service-key.json');
        $customPath = env('FIREBASE_CREDENTIALS') ?? env('FCM_SERVICE_ACCOUNT_PATH');

        $this->credentialsPath = ($customPath && file_exists($customPath)) ? $customPath : $defaultPath;
    }

    /**
     * Generate OAuth2 Access Token using Service Account JSON (FCM HTTP v1 API).
     */
    protected function getAccessToken(): ?array
    {
        if (!file_exists($this->credentialsPath)) {
            Log::warning("FCM v1: Service account JSON file not found at {$this->credentialsPath}");
            return null;
        }

        // Cache Google Access Token for 50 minutes (valid for 60 min)
        return Cache::remember('fcm_google_access_token', 3000, function () {
            $json = json_decode(file_get_contents($this->credentialsPath), true);

            if (!$json || !isset($json['private_key'], $json['client_email'], $json['project_id'])) {
                Log::error("FCM v1: Invalid service account JSON structure.");
                return null;
            }

            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claimSet = base64_encode(json_encode([
                'iss' => $json['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signatureInput = $header . '.' . $claimSet;
            $signature = '';

            $privateKey = $json['private_key'];
            if (!openssl_sign($signatureInput, $signature, $privateKey, 'SHA256')) {
                Log::error("FCM v1: Failed to sign JWT assertion.");
                return null;
            }

            $jwt = $signatureInput . '.' . base64_encode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                return [
                    'access_token' => $response->json()['access_token'],
                    'project_id' => $json['project_id'],
                ];
            }

            Log::error("FCM v1: Failed to fetch OAuth2 token from Google", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        });
    }

    /**
     * Send push notification to single token or array of device tokens.
     */
    public function sendPushNotification(string|array $tokens, string $title, string $body, array $data = []): bool
    {
        $tokens = is_array($tokens) ? array_filter($tokens) : [$tokens];

        if (empty($tokens)) {
            Log::info('FCM: No active device tokens found.');
            return false;
        }

        // Try FCM HTTP v1 API first (Service Account JSON)
        $authData = $this->getAccessToken();

        if ($authData) {
            return $this->sendHttpV1($tokens, $authData['project_id'], $authData['access_token'], $title, $body, $data);
        }

        // Fallback to legacy Server Key if configured
        $serverKey = config('services.fcm.server_key') ?? env('FCM_SERVER_KEY');
        if ($serverKey) {
            return $this->sendLegacy($tokens, $serverKey, $title, $body, $data);
        }

        Log::warning('FCM: Neither Service Account JSON nor FCM_SERVER_KEY is available. Push notification skipped.');
        return false;
    }

    /**
     * Send using FCM HTTP v1 API.
     */
    protected function sendHttpV1(array $tokens, string $projectId, string $accessToken, string $title, string $body, array $data): bool
    {
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
        $successCount = 0;

        // Stringify all data values for FCM v1 payload compatibility
        $formattedData = [];
        foreach ($data as $key => $value) {
            $formattedData[(string) $key] = is_null($value) ? '' : (string) $value;
        }

        foreach ($tokens as $token) {
            try {
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_merge([
                            'title' => $title,
                            'body' => $body,
                        ], $formattedData),
                    ],
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])->post($url, $payload);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    Log::error("FCM v1: Failed for token {$token}", [
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("FCM v1 Exception: " . $e->getMessage());
            }
        }

        Log::info("FCM v1: Sent {$successCount}/" . count($tokens) . " notifications successfully.");
        return $successCount > 0;
    }

    /**
     * Legacy FCM Send Fallback.
     */
    protected function sendLegacy(array $tokens, string $serverKey, string $title, string $body, array $data): bool
    {
        try {
            $payload = [
                'registration_ids' => array_values($tokens),
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => array_merge([
                    'title' => $title,
                    'body' => $body,
                ], $data),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("FCM Legacy Exception: " . $e->getMessage());
            return false;
        }
    }
}
