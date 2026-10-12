<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Expense;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class SyncFirebase extends Command
{
    protected $signature = 'firebase:sync';
    protected $description = 'Sync all revenues and expenses to Firebase and restore all members from cloud';

    public function handle()
    {
        $this->info('Starting Two-Way Cloud Synchronization with Google Firebase...');

        try {
            $fb = new FirebaseService();

            // =========================================================================
            // 1. PUSH ALL LOCAL REVENUES & PAYMENTS TO FIRESTORE
            // =========================================================================
            $this->info('Uploading revenues to Firebase collection...');
            $payments = Payment::where('status', 'verified')->with(['customer', 'member'])->get();
            $revCount = 0;

            foreach ($payments as $p) {
                $code = $p->payment_code ?: 'REV-' . $p->id;
                $fb->syncRevenue([
                    'revenue_code' => $code,
                    'amount'       => (float) $p->amount,
                    'revenue_date' => $p->created_at ? $p->created_at->format('Y-m-d') : date('Y-m-d'),
                    'notes'        => ($p->customer ? $p->customer->full_name : 'Member') . ' (' . ($p->payment_type === 'monthly_subscription' ? 'Monthly Pass' : 'Daily ₱50') . ')',
                ]);
                $revCount++;
            }
            $this->info("✓ Uploaded {$revCount} revenues to Firebase!");

            // =========================================================================
            // 2. PUSH ALL EXPENSES TO FIRESTORE
            // =========================================================================
            $this->info('Uploading expenses to Firebase collection...');
            $expenses = Expense::all();
            $expCount = 0;

            foreach ($expenses as $e) {
                $fb->syncExpense([
                    'expense_code' => $e->expense_code,
                    'category'     => $e->category,
                    'description'  => $e->description,
                    'amount'       => (float) $e->amount,
                    // Safe version – works whether expense_date is string or Carbon
                    'expense_date' => $e->expense_date
                        ? Carbon::parse($e->expense_date)->format('Y-m-d')
                        : date('Y-m-d'),
                ]);
                $expCount++;
            }
            $this->info("✓ Uploaded {$expCount} expenses to Firebase!");

            // =========================================================================
            // 3. PULL ALL CLOUD MEMBERS FROM FIRESTORE
            // =========================================================================
            $this->info('Fetching all cloud members from Firestore...');
            $cloudMembers = $fb->getAllCloudMembers();
            $recovered = 0;

            foreach ($cloudMembers as $cm) {
                $memberCode = $cm['member_code'];

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

                    $nameParts = explode(' ', trim($cm['name']));
                    $last = count($nameParts) > 1 ? array_pop($nameParts) : 'Member';
                    $first = count($nameParts) > 0 ? implode(' ', $nameParts) : 'User';

                    $customer = Customer::firstOrCreate(['email' => $email], [
                        'user_id'        => $user->id,
                        'first_name'     => $first,
                        'last_name'      => $last,
                        'contact_number' => $cm['contact_number'] ?: '09531704928',
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

            $this->info("✓ Restored and verified {$recovered} members from Firebase into live website (Total: " . count($cloudMembers) . " members)!");
            $this->info('Cloud synchronization completed successfully!');

        } catch (\Exception $e) {
            $this->error('Error during cloud sync: ' . $e->getMessage());
        }

        return 0;
    }
}