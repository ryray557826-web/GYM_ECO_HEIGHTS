<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PullFirebaseData extends Command
{
    protected $signature = 'firebase:pull';
    protected $description = 'Recover all members from Google Cloud Firestore into local database';

    public function handle()
    {
        $this->info('Checking Google Cloud Firestore for members...');

        try {
            $fb = new FirebaseService();
            $cloudMembers = $fb->getAllCloudMembers();

            $recovered = 0;
            foreach ($cloudMembers as $cm) {
                $memberCode = $cm['member_code'];

                // If member exists in Firebase but not in local SQLite, restore them!
                $existing = Member::where('member_code', $memberCode)->first();
                if (!$existing) {
                    $email = !empty($cm['email']) ? $cm['email'] : strtolower($memberCode) . '@ecoheights.local';
                    
                    $user = User::firstOrCreate(['email' => $email], [
                        'name'              => $cm['name'],
                        'password'          => Hash::make('pass123'),
                        'role'              => 'member',
                        'account_status'    => 'active',
                        'email_verified_at' => Carbon::now(),
                    ]);

                    $parts = explode(' ', trim($cm['name']), 2);
                    $first = $parts[0] ?? 'Member';
                    $last  = $parts[1] ?? 'User';

                    $customer = Customer::firstOrCreate(['email' => $email], [
                        'user_id'        => $user->id,
                        'first_name'     => $first,
                        'last_name'      => $last,
                        'contact_number' => $cm['contact_number'] ?: '09000000000',
                        'address'        => 'Davao City',
                    ]);

                    Member::create([
                        'customer_id'         => $customer->id,
                        'member_code'         => $memberCode,
                        'joined_date'         => Carbon::today(),
                        'verification_status' => 'verified',
                        'membership_status'   => $cm['status'] ?? 'active',
                        'reward_points'       => (int) ($cm['reward_points'] ?? 0),
                    ]);

                    $recovered++;
                }
            }

            $this->info("✓ Successfully recovered and preserved {$recovered} cloud members from Firebase!");
        } catch (\Exception $e) {
            $this->warn('Could not pull from Firebase: ' . $e->getMessage());
        }

        return 0;
    }
}