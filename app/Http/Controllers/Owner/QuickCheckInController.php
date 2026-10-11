<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickCheckInController extends Controller
{
    public function search(Request $request)
    {
        $query = strtoupper(trim($request->member_id));

        $member = Member::whereRaw('UPPER(member_code) = ?', [$query])
            ->with(['customer', 'latestSubscription.package'])
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => "No member found matching ID \"{$query}\"."
            ], 404);
        }

        $subscription = $member->latestSubscription;
        $package = $subscription ? $subscription->package : null;

        // Strict Check: Must hold active MONTHLY OR LONGER pass (>= 28 days) to get free check-in
        $isMonthlyOrMoreActive = $subscription && 
                                 $subscription->status === 'active' && 
                                 $package && 
                                 ($package->duration_in_days >= 28 || in_array($package->plan_type, ['monthly', 'quarterly', 'yearly', 'annual'])) &&
                                 Carbon::parse($subscription->end_time)->isFuture();

        $isDailyActive = $subscription &&
                         $subscription->status === 'active' &&
                         $package &&
                         ($package->plan_type === 'daily' || $package->duration_in_days <= 1) &&
                         Carbon::parse($subscription->end_time)->isFuture();

        $planTypeName = 'Walk-In';
        if ($package) {
            $planTypeName = $package->name . ' (' . ucfirst($package->plan_type) . ')';
        }

        $expiryLabel = 'No active subscription';
        if ($subscription && Carbon::parse($subscription->end_time)->isFuture()) {
            $expiryLabel = $isDailyActive 
                ? Carbon::parse($subscription->end_time)->format('Y-m-d') . ' (End of Day)'
                : Carbon::parse($subscription->end_time)->format('Y-m-d H:i');
        }

        $statusBadge = 'NO ACTIVE PASS';
        if ($isMonthlyOrMoreActive) {
            $statusBadge = 'ACTIVE (' . strtoupper($package->plan_type) . ')';
        } elseif ($isDailyActive) {
            $statusBadge = 'ACTIVE (DAILY PASS)';
        } elseif ($subscription && $subscription->status === 'expired') {
            $statusBadge = 'EXPIRED';
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id'                 => $member->id,
                'customer_id'        => $member->customer_id,
                'member_id'          => $member->member_code,
                'name'               => $member->customer ? $member->customer->full_name : 'No Name',
                'contact'            => $member->customer->contact_number ?? 'No contact provided',
                'has_active_monthly' => $isMonthlyOrMoreActive,
                'reward_points'      => $member->reward_points ?? 0,
                'plan_type'          => $planTypeName,
                'expires_at'         => $expiryLabel,
                'status'             => $statusBadge
            ]
        ]);
    }

    // Confirm ₱50 Paid Entry + Create Daily Subscription (Expires End of Day) + Award 3 Points
    public function confirmPerSession(Request $request)
    {
        $request->validate([
            'member_id'        => 'required|exists:members,id',
            'payment_method'   => 'nullable|in:cash,gcash',
            'reference_number' => 'nullable|string|max:100'
        ]);

        return DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $methodCode = $request->payment_method ?? 'cash';
            
            if ($methodCode === 'gcash') {
                $method = PaymentMethod::firstOrCreate(
                    ['code' => 'gcash'],
                    ['name' => 'GCash', 'is_online' => true, 'requires_reference' => true]
                );
            } else {
                $method = PaymentMethod::firstOrCreate(
                    ['code' => 'cash'],
                    ['name' => 'Physical / Walk-In', 'is_online' => false, 'requires_reference' => false]
                );
            }

            $today = Carbon::today()->toDateString();
            $now = Carbon::now()->toTimeString();

            // 1. Find or create Daily Package
            $dailyPackage = Package::firstOrCreate(
                ['plan_type' => 'daily'],
                ['package_code' => 'PKG-DAY-050', 'name' => 'Daily Walk-In Session', 'price' => 50.00, 'duration_in_days' => 1, 'is_active' => true]
            );

            // 2. Attach Daily Subscription that expires at the end of the day (23:59:59)
            $sub = MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $dailyPackage->id,
                'start_time' => Carbon::now(),
                'end_time'   => Carbon::today()->endOfDay(),
                'status'     => 'active'
            ]);

            // 3. Record Verified Payment
            $payment = Payment::create([
                'payment_code'           => 'PAY-' . Carbon::now()->format('YmdHis'),
                'customer_id'            => $member->customer_id,
                'member_id'              => $member->id,
                'member_subscription_id' => $sub->id,
                'payment_method_id'      => $method->id,
                'amount'                 => 50.00,
                'payment_type'           => 'per_session',
                'reference_number'       => $request->reference_number,
                'status'                 => 'verified',
                'verified_at'            => Carbon::now(),
                'verified_by'            => auth()->id()
            ]);

            // 4. Record in Revenue Ledger
            $budOps = Budget::firstOrCreate(
                ['budget_code' => 'BUD-2026-OPS'],
                ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00]
            );

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'budget_id'    => $budOps->id,
                'amount'       => 50.00,
                'revenue_date' => $today
            ]);

            // 5. Log Attendance
            Attendance::create([
                'customer_id'     => $member->customer_id,
                'member_id'       => $member->id,
                'payment_id'      => $payment->id,
                'attendance_date' => $today,
                'check_in_time'   => $now,
                'entry_type'      => 'per_session'
            ]);

            // 6. Update Member Status to Active & Award 3 Points
            $member->update(['membership_status' => 'active']);
            $member->increment('reward_points', 3);

            $methodLabel = ($methodCode === 'gcash') 
                ? "GCash" . ($request->reference_number ? " (Ref: {$request->reference_number})" : "") 
                : "Cash";

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Daily Pass Activated (₱50 via {$methodLabel}, Valid until End of Day) & +3 Points credited to {$member->customer->full_name}",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => 'End of Day (Today)',
                'performed_by'    => 'Owner'
            ]);

            return response()->json([
                'success' => true,
                'message' => "Daily pass activated until End of Day & 3 Points awarded to {$member->customer->full_name}!"
            ]);
        });
    }

    public function confirmMonthly(Request $request)
    {
        $request->validate(['member_id' => 'required|exists:members,id']);
        $member = Member::with(['customer', 'latestSubscription.package'])->findOrFail($request->member_id);

        $subscription = $member->latestSubscription;
        $package = $subscription ? $subscription->package : null;

        if (!$subscription || $subscription->status !== 'active' || !$package || $package->duration_in_days < 28 || Carbon::parse($subscription->end_time)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not have an active monthly or longer subscription. Paid daily entry required.'
            ], 422);
        }

        Attendance::create([
            'customer_id'     => $member->customer_id,
            'member_id'       => $member->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_in_time'   => Carbon::now()->toTimeString(),
            'entry_type'      => 'membership'
        ]);

        AuditLog::create([
            'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
            'user_id'         => auth()->id(),
            'action'          => "Active Monthly Pass Check-in logged for {$member->customer->full_name}",
            'entity_type'     => Member::class,
            'entity_id'       => $member->id,
            'validity_period' => 'Member Pass Entry',
            'performed_by'    => 'Owner'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Active pass member {$member->customer->full_name} checked in successfully!"
        ]);
    }
}