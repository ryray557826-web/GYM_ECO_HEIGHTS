<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FirebaseService
{
    protected string $projectId;
    protected string $baseUrl;
    protected ?array $credentials = null;

    public function __construct()
    {
        $credPath = storage_path('app/firebase/firebase_credentials.json');

        if (file_exists($credPath)) {
            $this->credentials = json_decode(file_get_contents($credPath), true);
            $this->projectId = $this->credentials['project_id'] ?? env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        } else {
            $this->projectId = env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        }

        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /**
     * Generate an OAuth2 Bearer Token using your service account private key
     */
    protected function getAccessToken(): ?string
    {
        if (!$this->credentials) {
            return null;
        }

        return Cache::remember('firebase_access_token', 3300, function () {
            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            
            $payload = base64_encode(json_encode([
                'iss'   => $this->credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'exp'   => $now + 3600,
                'iat'   => $now,
            ]));

            $rawSignature = '';
            openssl_sign("{$header}.{$payload}", $rawSignature, $this->credentials['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = "{$header}.{$payload}." . str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($rawSignature));

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            return $response->json('access_token');
        });
    }

    protected function client()
    {
        $token = $this->getAccessToken();
        return $token ? Http::withToken($token) : Http::baseUrl('');
    }

    /**
     * Convert standard PHP array to Firestore Typed Fields
     */
    public function formatFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $fields[$key] = ['nullValue' => null];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } elseif (is_int($value)) {
                $fields[$key] = ['integerValue' => (string) $value];
            } elseif (is_float($value) || (is_numeric($value) && strpos((string)$value, '.') !== false)) {
                $fields[$key] = ['doubleValue' => (float) $value];
            } else {
                $fields[$key] = ['stringValue' => (string) $value];
            }
        }
        return ['fields' => $fields];
    }

    /**
     * Set a Document in a Collection with a custom ID
     */
    public function setDocument(string $collection, string $docId, array $data)
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}";
        $payload = $this->formatFields($data);
        return $this->client()->patch($url, $payload)->json();
    }

    /**
     * Add a Document in a Collection with an auto-generated ID
     */
    public function addDocument(string $collection, array $data)
    {
        $url = "{$this->baseUrl}/{$collection}";
        $payload = $this->formatFields($data);
        return $this->client()->post($url, $payload)->json();
    }

    public function syncMember(array $memberData)
    {
        return $this->setDocument('members', $memberData['member_code'], [
            'member_code'       => $memberData['member_code'],
            'name'              => $memberData['name'],
            'email'             => $memberData['email'] ?? '',
            'contact_number'    => $memberData['contact_number'] ?? '',
            'membership_status' => $memberData['status'] ?? 'active',
            'reward_points'     => (int) ($memberData['reward_points'] ?? 0),
            'updated_at'        => now()->toDateTimeString(),
        ]);
    }
}