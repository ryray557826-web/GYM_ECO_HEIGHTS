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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentApprovalController extends Controller
{
    public function index()
    {
        $members = Member::with('customer')->get();
        $paymentMethods = PaymentMethod::all();
        $pendingPayments = Payment::where('status', 'pending')->with(['customer', 'member'])->latest()->get();
        $payments = Payment::with(['customer', 'member', 'method'])->latest()->paginate(20);

        return view('owner.payments', compact('members', 'paymentMethods', 'pendingPayments', 'payments'));
    }

    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'      => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            // 1. Recognize revenue
            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'amount'       => $payment->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            // 2. Resolve member and calculate points dynamically
            $member = $payment->member ?? ($payment->customer ? $payment->customer->member : null);

            $daysToAdd = 30;
            $pointsAwarded = 15; // default 1 month

            if ($payment->member_subscription_id && $payment->subscription && $payment->subscription->package) {
                $pkg = $payment->subscription->package;
                $daysToAdd = $pkg->duration_in_days;

                if ($daysToAdd >= 365 || $payment->amount >= 7000) {
                    $pointsAwarded = 180; // Yearly: 15 pts x 12
                } elseif ($daysToAdd >= 90 || $payment->amount >= 2000) {
                    $pointsAwarded = 45;  // Quarterly: 15 pts x 3
                } else {
                    $pointsAwarded = 15;  // Monthly
                }
            } elseif ($payment->payment_type === 'per_session' || $payment->amount <= 50) {
                $pointsAwarded = 3;       // Daily session
                $daysToAdd = 1;
            }

            // Award points directly to member
            if ($member) {
                $member->increment('reward_points', $pointsAwarded);
                $member->update(['membership_status' => 'active']);
            }

            // 3. Extend or activate subscription
            if ($payment->member_subscription_id && $payment->subscription) {
                $payment->subscription->update([
                    'start_time' => Carbon::now(),
                    'end_time'   => Carbon::now()->addDays($daysToAdd),
                    'status'     => 'active'
                ]);
            }

            // 4. Log to Audit Trail
            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Payment #{$payment->payment_code} Approved (₱{$payment->amount}) & +{$pointsAwarded} PTS credited",
                    'validity_period' => "{$daysToAdd} Days",
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', "Payment {$payment->payment_code} verified and points credited.");
    }

    public function reject(Payment $payment)
    {
        $payment->update(['status' => 'rejected']);
        return back()->with('warning', "Payment {$payment->payment_code} rejected.");
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'member_id'         => 'required|exists:members,id',
            'plan_type'         => 'required|in:monthly,quarterly,yearly,per_session',
            'amount'            => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number'  => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');

            $isSubscription = in_array($request->plan_type, ['monthly', 'quarterly', 'yearly']);

            $payment = Payment::create([
                'payment_code'      => $paymentCode,
                'customer_id'       => $member->customer_id,
                'member_id'         => $member->id,
                'payment_method_id' => $request->payment_method_id,
                'amount'            => $request->amount,
                'payment_type'      => $isSubscription ? 'monthly_subscription' : 'per_session',
                'reference_number'  => $request->reference_number,
                'status'            => 'verified',
                'verified_at'       => Carbon::now(),
                'verified_by'       => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'amount'       => $request->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            // Calculate points and validity days based on schedule
            $pointsAwarded = 3;
            $daysToAdd = 1;

            if ($request->plan_type === 'yearly' || $request->amount >= 7000) {
                $pointsAwarded = 180;
                $daysToAdd = 365;
            } elseif ($request->plan_type === 'quarterly' || $request->amount >= 2000) {
                $pointsAwarded = 45;
                $daysToAdd = 90;
            } elseif ($request->plan_type === 'monthly' || $request->amount >= 700) {
                $pointsAwarded = 15;
                $daysToAdd = 30;
            }

            // Award points
            $member->increment('reward_points', $pointsAwarded);
            $member->update(['membership_status' => 'active']);

            if ($isSubscription) {
                $pkg = Package::firstOrCreate(
                    ['plan_type' => $request->plan_type],
                    ['package_code' => 'PKG-' . strtoupper($request->plan_type), 'name' => ucfirst($request->plan_type) . ' Pass', 'price' => $request->amount, 'duration_in_days' => $daysToAdd]
                );

                $sub = MemberSubscription::create([
                    'member_id'  => $member->id,
                    'package_id' => $pkg->id,
                    'start_time' => Carbon::now(),
                    'end_time'   => Carbon::now()->addDays($daysToAdd),
                    'status'     => 'active'
                ]);

                $payment->update(['member_subscription_id' => $sub->id]);
            }

            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Manual Payment (₱{$request->amount}) logged with +{$pointsAwarded} PTS for {$member->customer->full_name}",
                    'validity_period' => "{$daysToAdd} Days",
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', 'Manual payment logged, points credited, and membership activated.');
    }
}