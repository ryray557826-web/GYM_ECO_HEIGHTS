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
        $credPath = file_exists('/etc/secrets/firebase_credentials.json')
            ? '/etc/secrets/firebase_credentials.json'
            : storage_path('app/firebase/firebase_credentials.json');

        if (file_exists($credPath)) {
            $this->credentials = json_decode(file_get_contents($credPath), true);
            $this->projectId = $this->credentials['project_id'] ?? env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        } else {
            $this->projectId = env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        }

        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

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

    public function setDocument(string $collection, string $docId, array $data)
    {
        $url = "{$this->baseUrl}/{$collection}/{$docId}";
        $payload = $this->formatFields($data);
        return $this->client()->patch($url, $payload)->json();
    }

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

    // Sync Revenue into Firebase Firestore
    public function syncRevenue(array $data)
    {
        return $this->setDocument('revenues', $data['revenue_code'], [
            'revenue_code' => (string) $data['revenue_code'],
            'amount'       => (float) $data['amount'],
            'revenue_date' => (string) $data['revenue_date'],
            'notes'        => (string) ($data['notes'] ?? 'Verified Payment'),
            'created_at'   => now()->toDateTimeString(),
        ]);
    }

    // Sync Expense into Firebase Firestore
    public function syncExpense(array $data)
    {
        return $this->setDocument('expenses', $data['expense_code'], [
            'expense_code' => (string) $data['expense_code'],
            'category'     => (string) $data['category'],
            'description'  => (string) $data['description'],
            'amount'       => (float) $data['amount'],
            'expense_date' => (string) $data['expense_date'],
            'created_at'   => now()->toDateTimeString(),
        ]);
    }

    /**
     * Fetch ALL members from Firestore with pageSize=300 (Fixes 20-doc cutoff)
     */
    public function getAllCloudMembers(): array
    {
        $url = "{$this->baseUrl}/members?pageSize=300";
        $response = $this->client()->get($url);
        $docs = $response->json('documents') ?? [];

        $members = [];
        foreach ($docs as $doc) {
            $f = $doc['fields'] ?? [];
            if (!empty($f['member_code']['stringValue'])) {
                $members[] = [
                    'member_code'    => $f['member_code']['stringValue'],
                    'name'           => $f['name']['stringValue'] ?? 'Member',
                    'email'          => $f['email']['stringValue'] ?? '',
                    'contact_number' => $f['contact_number']['stringValue'] ?? '',
                    'status'         => $f['membership_status']['stringValue'] ?? 'active',
                    'reward_points'  => (int) ($f['reward_points']['integerValue'] ?? 0),
                ];
            }
        }
        return $members;
    }
}