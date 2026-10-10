<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Member;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\MemberSubscription;
use App\Models\AuditLog;
use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentApprovalController extends Controller
{
    /**
     * Display the Owner Payments Ledger and Approval Queue
     */
    public function index()
    {
        $members = Member::with(['customer', 'latestSubscription.package'])->get();
        $packages = Package::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::all();
        $pendingPayments = Payment::where('status', 'pending')->with(['customer', 'member'])->latest()->get();
        $payments = Payment::with(['customer', 'member', 'method'])->latest()->paginate(20);

        return view('owner.payments', compact('members', 'packages', 'paymentMethods', 'pendingPayments', 'payments'));
    }

    /**
     * Verify an Online or Physical Member Payment
     */
    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'      => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            // 1. Recognize in Revenue Ledger
            $budOps = Budget::firstOrCreate(
                ['budget_code' => 'BUD-2026-OPS'],
                ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00]
            );

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'budget_id'    => $budOps->id,
                'amount'       => $payment->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            // 2. Resolve Member (Direct or through Customer profile)
            $member = $payment->member ?? ($payment->customer ? $payment->customer->member : null);

            $daysToAdd = 30;
            $pointsAwarded = 15;
            $isDaily = false;

            if ($payment->member_subscription_id && $payment->subscription && $payment->subscription->package) {
                $pkg = $payment->subscription->package;
                $daysToAdd = $pkg->duration_in_days;

                if ($pkg->plan_type === 'daily' || $daysToAdd <= 1) {
                    $isDaily = true;
                    $pointsAwarded = 3;
                } elseif ($daysToAdd >= 365 || $payment->amount >= 7000) {
                    $pointsAwarded = 180; // Yearly
                } elseif ($daysToAdd >= 90 || $payment->amount >= 2000) {
                    $pointsAwarded = 45;  // Quarterly
                } else {
                    $pointsAwarded = 15;  // Monthly
                }
            } elseif ($payment->payment_type === 'per_session' || $payment->amount <= 50) {
                $isDaily = true;
                $pointsAwarded = 3;
                $daysToAdd = 1;
            }

            // Award points and activate member status
            if ($member) {
                $member->increment('reward_points', $pointsAwarded);
                $member->update(['membership_status' => 'active']);
            }

            // 3. Extend or Activate Subscription (Daily expires at end-of-day; longer passes add full days)
            if ($payment->member_subscription_id && $payment->subscription) {
                $newEndTime = $isDaily ? Carbon::today()->endOfDay() : Carbon::now()->addDays($daysToAdd);

                $payment->subscription->update([
                    'start_time' => Carbon::now(),
                    'end_time'   => $newEndTime,
                    'status'     => 'active'
                ]);
            }

            // 4. System Audit Trail
            if (class_exists(AuditLog::class)) {
                $validityString = $isDaily ? 'End of Day (Today)' : "{$daysToAdd} Days";

                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Payment #{$payment->payment_code} Verified (₱{$payment->amount}) & +{$pointsAwarded} PTS credited",
                    'validity_period' => $validityString,
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', "Payment {$payment->payment_code} verified and subscription updated.");
    }

    /**
     * Reject a Payment Request
     */
    public function reject(Payment $payment)
    {
        $payment->update(['status' => 'rejected']);
        return back()->with('warning', "Payment {$payment->payment_code} rejected.");
    }

    /**
     * Record a Manual Payment with Fixed Package Pricing
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'member_id'         => 'required|exists:members,id',
            'package_id'        => 'required|exists:packages,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number'  => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $package = Package::findOrFail($request->package_id);
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');

            $isDaily = ($package->plan_type === 'daily' || $package->duration_in_days <= 1);
            $amount = $package->price; // Fixed package rate

            // 1. Record Verified Payment
            $payment = Payment::create([
                'payment_code'      => $paymentCode,
                'customer_id'       => $member->customer_id,
                'member_id'         => $member->id,
                'payment_method_id' => $request->payment_method_id,
                'amount'            => $amount,
                'payment_type'      => $isDaily ? 'per_session' : 'monthly_subscription',
                'reference_number'  => $request->reference_number,
                'status'            => 'verified',
                'verified_at'       => Carbon::now(),
                'verified_by'       => auth()->id()
            ]);

            // 2. Recognize in Revenue Ledger
            $budOps = Budget::firstOrCreate(
                ['budget_code' => 'BUD-2026-OPS'],
                ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00]
            );

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'budget_id'    => $budOps->id,
                'amount'       => $amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            // 3. Allocate Points based on Plan
            $pointsAwarded = 15;
            if ($package->duration_in_days >= 365) $pointsAwarded = 180;
            elseif ($package->duration_in_days >= 90) $pointsAwarded = 45;
            elseif ($isDaily) $pointsAwarded = 3;

            $member->increment('reward_points', $pointsAwarded);
            $member->update(['membership_status' => 'active']);

            // 4. Create Active Subscription (Daily ends today at 23:59:59)
            $startTime = Carbon::now();
            $endTime = $isDaily ? Carbon::today()->endOfDay() : Carbon::now()->addDays($package->duration_in_days);

            $sub = MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $package->id,
                'start_time' => $startTime,
                'end_time'   => $endTime,
                'status'     => 'active'
            ]);

            $payment->update(['member_subscription_id' => $sub->id]);

            // 5. Audit Log
            if (class_exists(AuditLog::class)) {
                $validityPeriod = $isDaily ? 'End of Day (Today)' : "{$package->duration_in_days} Days";

                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Manual Payment for {$package->name} (₱{$amount}) with +{$pointsAwarded} PTS credited",
                    'validity_period' => $validityPeriod,
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', 'Manual payment logged with fixed rate, points credited, and subscription activated.');
    }
}