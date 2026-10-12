<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Revenue;
use App\Models\Budget;
use App\Models\Attendance;
use Carbon\Carbon;

class PopulateMemberData extends Command
{
    protected $signature = 'members:populate {--force}';
    protected $description = 'One-time: Give ALL members realistic passes using the seeded packages, plus revenue and attendance';

    public function handle()
    {
        $this->info('Starting FORCE member data population using seeded packages...');

        // Use ONLY the packages from DatabaseSeeder
        $daily     = Package::where('package_code', 'PKG-DAY-050')->first();
        $monthly   = Package::where('package_code', 'PKG-MTH-750')->first();
        $quarterly = Package::where('package_code', 'PKG-QTR-2100')->first();
        $yearly    = Package::where('package_code', 'PKG-YRL-7500')->first();

        if (!$daily || !$monthly || !$quarterly || !$yearly) {
            $this->error('Seeded packages not found! Please run php artisan db:seed first.');
            return 1;
        }

        $cashMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Physical / Walk-In']);

        $budget = Budget::firstOrCreate(
            ['budget_code' => 'BUD-2026-OPS'],
            ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00]
        );

        $members = Member::with(['customer', 'subscriptions'])->get();
        $this->info("Found {$members->count()} members...");

        $createdSubs = 0;
        $createdPayments = 0;
        $createdAttendances = 0;

        foreach ($members as $member) {
            // Force clean old incomplete data
            $member->subscriptions()->delete();

            $points = (int) ($member->reward_points ?? 0);

            // Map reward points to the correct seeded package
            if ($points >= 180) {
                $package = $yearly;          // Yearly VIP
            } elseif ($points >= 45) {
                $package = $quarterly;       // Quarterly
            } elseif ($points >= 15) {
                $package = $monthly;         // Monthly
            } else {
                $package = $daily;           // Daily
            }

            $isDaily = $package->plan_type === 'daily' || $package->duration_in_days <= 1;

            $daysAgo = rand(5, $package->duration_in_days + 15);
            $startTime = Carbon::now()->subDays($daysAgo);
            $endTime = $isDaily
                ? $startTime->copy()->endOfDay()
                : $startTime->copy()->addDays($package->duration_in_days);

            $isStillActive = $endTime->isFuture();

            $sub = MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $package->id,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'status'     => $isStillActive ? 'active' : 'expired',
            ]);
            $createdSubs++;

            $member->update([
                'membership_status'   => $isStillActive ? 'active' : 'expired',
                'verification_status' => 'verified',
            ]);

            $payment = Payment::create([
                'payment_code'           => 'PAY-' . $member->member_code . '-' . time() . rand(10, 99),
                'customer_id'            => $member->customer_id,
                'member_id'              => $member->id,
                'member_subscription_id' => $sub->id,
                'payment_method_id'      => $cashMethod->id,
                'amount'                 => $package->price,
                'payment_type'           => $isDaily ? 'per_session' : 'monthly_subscription',
                'status'                 => 'verified',
                'verified_at'            => $startTime,
                'verified_by'            => 1,
                'created_at'             => $startTime,
                'updated_at'             => $startTime,
            ]);
            $createdPayments++;

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'budget_id'    => $budget->id,
                'amount'       => $package->price,
                'revenue_date' => $startTime->toDateString(),
                'created_at'   => $startTime,
                'updated_at'   => $startTime,
            ]);

            // Correct entry_type values
            $entryType = $isDaily ? 'per_session' : 'membership';

            $attCount = rand(2, 5);
            for ($i = 0; $i < $attCount; $i++) {
                $attDate = $startTime->copy()->addDays(rand(0, min(25, max(1, $package->duration_in_days - 1))));
                if ($attDate->isFuture()) {
                    $attDate = Carbon::yesterday();
                }

                Attendance::create([
                    'member_id'       => $member->id,
                    'customer_id'     => $member->customer_id,
                    'attendance_date' => $attDate->toDateString(),
                    'check_in_time'   => $attDate->copy()->setTime(rand(6, 21), rand(0, 59), 0)->toTimeString(),
                    'entry_type'      => $entryType,
                    'payment_id'      => $isDaily ? $payment->id : null,
                ]);
                $createdAttendances++;
            }

            $this->line("✓ {$member->member_code} → {$package->name} (" . ($isStillActive ? 'ACTIVE' : 'EXPIRED') . ")");
        }

        $this->newLine();
        $this->info("✓ Created {$createdSubs} subscriptions");
        $this->info("✓ Created {$createdPayments} payments + revenue");
        $this->info("✓ Created {$createdAttendances} attendance records");
        $this->info('Done! Refresh the Members page now.');

        return 0;
    }
}