<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickCheckInController extends Controller
{
    /**
     * Search member by Member ID and strictly verify if they hold
     * an active subscription that is MONTHLY OR LONGER (>= 28 days)
     */
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

        // Strict Check: Must be active, unexpired, AND duration must be MONTHLY OR LONGER (>= 28 days)
        $isMonthlyOrMoreActive = $subscription && 
                                 $subscription->status === 'active' && 
                                 $package && 
                                 ($package->duration_in_days >= 28 || in_array($package->plan_type, ['monthly', 'quarterly', 'yearly', 'annual'])) &&
                                 Carbon::parse($subscription->end_time)->isFuture();

        $planTypeName = 'Walk-In';
        if ($package) {
            $planTypeName = $package->name . ' (' . ucfirst($package->plan_type) . ')';
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id'                 => $member->id,
                'customer_id'        => $member->customer_id,
                'member_id'          => $member->member_code,
                'name'               => $member->customer ? $member->customer->full_name : 'No Name',
                'contact'            => $member->customer->contact_number ?? 'No contact provided',
                'has_active_monthly' => $isMonthlyOrMoreActive, // Strictly true ONLY if monthly or longer
                'reward_points'      => $member->reward_points ?? 0,
                'plan_type'          => $planTypeName,
                'expires_at'         => ($subscription && Carbon::parse($subscription->end_time)->isFuture()) 
                                            ? Carbon::parse($subscription->end_time)->format('Y-m-d H:i') 
                                            : 'No active subscription',
                'status'             => $isMonthlyOrMoreActive ? 'ACTIVE PASS' : ($subscription && $subscription->status === 'expired' ? 'EXPIRED' : 'NO ACTIVE PASS')
            ]
        ]);
    }

    /**
     * Confirm ₱50 Paid Entry (Cash or GCash with Reference Number) + Award 3 Points
     */
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

            $payment = Payment::create([
                'payment_code'      => 'PAY-' . Carbon::now()->format('YmdHis'),
                'customer_id'       => $member->customer_id,
                'member_id'         => $member->id,
                'payment_method_id' => $method->id,
                'amount'            => 50.00,
                'payment_type'      => 'per_session',
                'reference_number'  => $request->reference_number,
                'status'            => 'verified',
                'verified_at'       => Carbon::now(),
                'verified_by'       => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'amount'       => 50.00,
                'revenue_date' => $today
            ]);

            Attendance::create([
                'customer_id'     => $member->customer_id,
                'member_id'       => $member->id,
                'payment_id'      => $payment->id,
                'attendance_date' => $today,
                'check_in_time'   => $now,
                'entry_type'      => 'per_session'
            ]);

            // Daily payment awards 3 points
            $member->increment('reward_points', 3);

            $methodLabel = ($methodCode === 'gcash') 
                ? "GCash" . ($request->reference_number ? " (Ref: {$request->reference_number})" : "") 
                : "Cash";

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Daily Payment (₱50 via {$methodLabel}) & +3 Points credited to {$member->customer->full_name}",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => '24 Hours',
                'performed_by'    => 'Owner'
            ]);

            return response()->json([
                'success' => true,
                'message' => "₱50 entry via {$methodLabel} recorded & 3 Points awarded to {$member->customer->full_name}!"
            ]);
        });
    }

    /**
     * Confirm Monthly Free Check-in (₱0) - Strictly for Monthly or Longer members
     */
    public function confirmMonthly(Request $request)
    {
        $request->validate(['member_id' => 'required|exists:members,id']);
        $member = Member::with(['customer', 'latestSubscription.package'])->findOrFail($request->member_id);

        $subscription = $member->latestSubscription;
        $package = $subscription ? $subscription->package : null;

        // Security check: Must hold unexpired monthly or longer subscription
        if (!$subscription || $subscription->status !== 'active' || !$package || $package->duration_in_days < 28 || Carbon::parse($subscription->end_time)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Member does not have an active monthly or longer subscription. Paid entry required.'
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