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
            // Automatically read the exact project_id from the JSON file!
            $this->projectId = $this->credentials['project_id'] ?? env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        } else {
            $this->projectId = env('FIREBASE_PROJECT_ID', 'eco-heights-gym-c200d');
        }

        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /**
     * Generate an OAuth2 Bearer Token using your service account private key (Zero extra packages required)
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

    /**
     * HTTP Client with Google Bearer Token
     */
    protected function client()
    {
        $token = $this->getAccessToken();
        return $token ? Http::withToken($token) : Http::baseUrl('');
    }

    /**
     * Convert PHP Array to Firestore Typed Fields
     */
    protected function formatFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_int($value) || (is_numeric($value) && !is_float($value))) {
                $fields[$key] = ['integerValue' => (string) $value];
            } elseif (is_bool($value)) {
                $fields[$key] = ['booleanValue' => $value];
            } else {
                $fields[$key] = ['stringValue' => (string) $value];
            }
        }
        return ['fields' => $fields];
    }

    /**
     * Sync or Add a Member to Firestore
     */
    public function syncMember(array $memberData)
    {
        $docId = $memberData['member_code'];
        $url = "{$this->baseUrl}/members/{$docId}";

        $payload = $this->formatFields([
            'member_code'       => $memberData['member_code'],
            'name'              => $memberData['name'],
            'email'             => $memberData['email'] ?? '',
            'contact_number'    => $memberData['contact_number'] ?? '',
            'membership_status' => $memberData['status'] ?? 'active',
            'reward_points'     => $memberData['reward_points'] ?? 0,
            'updated_at'        => now()->toDateTimeString(),
        ]);

        return $this->client()->patch($url, $payload)->json();
    }

    /**
     * Log a Real-time Attendance Entry
     */
    public function logAttendance(array $attendanceData)
    {
        $url = "{$this->baseUrl}/attendances";

        $payload = $this->formatFields([
            'member_code'     => $attendanceData['member_code'] ?? 'WALK-IN',
            'customer_name'   => $attendanceData['customer_name'] ?? 'Visitor',
            'entry_type'      => $attendanceData['entry_type'] ?? 'per_session',
            'attendance_date' => $attendanceData['attendance_date'] ?? date('Y-m-d'),
            'check_in_time'   => $attendanceData['check_in_time'] ?? date('H:i:s'),
        ]);

        return $this->client()->post($url, $payload)->json();
    }

    /**
     * Publish an Announcement to Firestore
     */
    public function publishAnnouncement(array $data)
    {
        $url = "{$this->baseUrl}/announcements";

        $payload = $this->formatFields([
            'title'       => $data['title'],
            'badge'       => $data['badge'] ?? 'INFO',
            'message'     => $data['message'],
            'posted_date' => date('Y-m-d'),
        ]);

        return $this->client()->post($url, $payload)->json();
    }

    /**
     * Fetch all Members from Firestore
     */
    public function getMembers(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/members");
        $documents = $response->json('documents') ?? [];

        $members = [];
        foreach ($documents as $doc) {
            $fields = $doc['fields'] ?? [];
            $members[] = [
                'member_code'   => $fields['member_code']['stringValue'] ?? '',
                'name'          => $fields['name']['stringValue'] ?? '',
                'reward_points' => $fields['reward_points']['integerValue'] ?? 0,
                'status'        => $fields['membership_status']['stringValue'] ?? 'active',
            ];
        }

        return $members;
    }
}