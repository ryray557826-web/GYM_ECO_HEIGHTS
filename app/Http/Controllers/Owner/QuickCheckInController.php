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
        $isMonthlyActive = $subscription && 
                           $subscription->status === 'active' && 
                           Carbon::parse($subscription->end_time)->isFuture();

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'customer_id' => $member->customer_id,
                'member_id' => $member->member_code,
                'name' => $member->customer->full_name,
                'contact' => $member->customer->contact_number ?? 'No contact provided',
                'has_active_monthly' => $isMonthlyActive,
                'plan_type' => $subscription && $subscription->package ? $subscription->package->name : 'Walk-In',
                'expires_at' => $subscription ? Carbon::parse($subscription->end_time)->format('Y-m-d H:i') : 'N/A',
                'status' => $isMonthlyActive ? 'ACTIVE' : ($subscription && $subscription->status === 'expired' ? 'EXPIRED' : 'NO PLAN')
            ]
        ]);
    }

    public function confirmPerSession(Request $request)
    {
        $request->validate(['member_id' => 'required|exists:members,id']);

        return DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $cashMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Physical Cash']);
            $today = Carbon::today()->toDateString();
            $now = Carbon::now()->toTimeString();

            // 1. Record verified payment
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');
            $payment = Payment::create([
                'payment_code' => $paymentCode,
                'customer_id' => $member->customer_id,
                'member_id' => $member->id,
                'payment_method_id' => $cashMethod->id,
                'amount' => 50.00,
                'payment_type' => 'per_session',
                'status' => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            // 2. Recognize Revenue
            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id' => $payment->id,
                'amount' => 50.00,
                'revenue_date' => $today
            ]);

            // 3. Log Attendance
            Attendance::create([
                'customer_id' => $member->customer_id,
                'member_id' => $member->id,
                'payment_id' => $payment->id,
                'attendance_date' => $today,
                'check_in_time' => $now,
                'entry_type' => 'per_session'
            ]);

            // 4. Audit Log
            AuditLog::create([
                'log_code' => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'action' => "Daily Access Granted (₱50) for {$member->customer->full_name}",
                'entity_type' => Member::class,
                'entity_id' => $member->id,
                'validity_period' => '24 Hours',
                'performed_by' => 'Owner'
            ]);

            return response()->json([
                'success' => true,
                'message' => "₱50 Per-Session entry confirmed and logged for {$member->customer->full_name}."
            ]);
        });
    }

    public function confirmMonthly(Request $request)
    {
        $request->validate(['member_id' => 'required|exists:members,id']);
        $member = Member::with('customer')->findOrFail($request->member_id);

        Attendance::create([
            'customer_id' => $member->customer_id,
            'member_id' => $member->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_in_time' => Carbon::now()->toTimeString(),
            'entry_type' => 'membership'
        ]);

        return response()->json([
            'success' => true,
            'message' => "Monthly member {$member->customer->full_name} checked in successfully!"
        ]);
    }
}