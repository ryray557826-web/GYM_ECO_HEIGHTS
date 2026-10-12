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
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PopulateMemberData extends Command
{
    protected $signature = 'members:populate';
    protected $description = 'One-time: Give all restored members realistic passes, payments, revenue and attendance';

    public function handle()
    {
        $this->info('Starting one-time member data population...');

        // 1. Ensure packages exist
        $daily = Package::firstOrCreate(
            ['package_code' => 'PKG-DAILY'],
            [
                'name' => 'Daily Walk-in',
                'plan_type' => 'daily',
                'price' => 50.00,
                'duration_in_days' => 1,
                'is_active' => true,
            ]
        );

        $monthly = Package::firstOrCreate(
            ['package_code' => 'PKG-MONTHLY'],
            [
                'name' => 'Monthly Pass',
                'plan_type' => 'monthly',
                'price' => 800.00,
                'duration_in_days' => 30,
                'is_active' => true,
            ]
        );

        $quarterly = Package::firstOrCreate(
            ['package_code' => 'PKG-QUARTERLY'],
            [
                'name' => 'Quarterly Pass',
                'plan_type' => 'quarterly',
                'price' => 2100.00,
                'duration_in_days' => 90,
                'is_active' => true,
            ]
        );

        $annual = Package::firstOrCreate(
            ['package_code' => 'PKG-ANNUAL'],
            [
                'name' => 'Annual Pass',
                'plan_type' => 'annual',
                'price' => 7500.00,
                'duration_in_days' => 365,
                'is_active' => true,
            ]
        );

        $cashMethod = PaymentMethod::firstOrCreate(
            ['code' => 'cash'],
            ['name' => 'Physical / Walk-In']
        );

        $budget = Budget::firstOrCreate(
            ['budget_code' => 'BUD-2026-OPS'],
            [
                'category_name' => 'Facility Operations',
                'allocated_amount' => 35000.00,
                'spent_amount' => 0.00,
            ]
        );

        $members = Member::with('customer')->get();
        $this->info("Found {$members->count()} members to process...");

        $createdSubs = 0;
        $createdPayments = 0;
        $createdAttendances = 0;

        foreach ($members as $member) {
            // Skip if they already have a subscription
            if ($member->subscriptions()->exists()) {
                continue;
            }

            $points = (int) ($member->reward_points ?? 0);

            // Decide package based on reward points (same logic as original store method)
            if ($points >= 180) {
                $package = $annual;
            } elseif ($points >= 45) {
                $package = $quarterly;
            } elseif ($points >= 15) {
                $package = $monthly;
            } else {
                $package = $daily; // 3 points or less
            }

            $isDaily = $package->plan_type === 'daily' || $package->duration_in_days <= 1;

            // Create a realistic start date in the past
            $startTime = Carbon::now()->subDays(rand(1, $package->duration_in_days + 5));
            $endTime = $isDaily
                ? $startTime->copy()->endOfDay()
                : $startTime->copy()->addDays($package->duration_in_days);

            $isStillActive = $endTime->isFuture();

            // Create subscription
            $sub = MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $package->id,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'status'     => $isStillActive ? 'active' : 'expired',
            ]);
            $createdSubs++;

            // Update member status
            $member->update([
                'membership_status' => $isStillActive ? 'active' : 'expired',
            ]);

            // Create payment + revenue
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis') . '-' . $member->id;

            $payment = Payment::create([
                'payment_code'           => $paymentCode,
                'customer_id'            => $member->customer_id,
                'member_id'              => $member->id,
                'member_subscription_id' => $sub->id,
                'payment_method_id'      => $cashMethod->id,
                'amount'                 => $package->price,
                'payment_type'           => $isDaily ? 'per_session' : 'monthly_subscription',
                'status'                 => 'verified',
                'verified_at'            => $startTime,
                'verified_by'            => 1, // assume owner id 1
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

            // Create 1–4 past attendance records
            $attendanceCount = rand(1, 4);
            for ($i = 0; $i < $attendanceCount; $i++) {
                $attDate = $startTime->copy()->addDays(rand(0, min(20, $package->duration_in_days)));
                if ($attDate->isFuture()) {
                    $attDate = Carbon::yesterday();
                }

                Attendance::create([
                    'member_id'        => $member->id,
                    'customer_id'      => $member->customer_id,
                    'attendance_date'  => $attDate->toDateString(),
                    'check_in_time'    => $attDate->copy()->setTime(rand(6, 20), rand(0, 59)),
                    'entry_type'       => $isDaily ? 'per_session' : 'subscription',
                    'payment_id'       => $isDaily ? $payment->id : null,
                ]);
                $createdAttendances++;
            }
        }

        $this->info("✓ Created {$createdSubs} subscriptions");
        $this->info("✓ Created {$createdPayments} payments + revenue records");
        $this->info("✓ Created {$createdAttendances} attendance records");
        $this->info('Done! Refresh the Members page and top stats.');

        return 0;
    }
}