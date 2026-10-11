<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\PaymentMethod;
use App\Models\Budget;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class MemberManagementController extends Controller
{
    public function index()
    {
        // 1. Automatically expire any subscriptions past their end time (end-of-day for daily, 30+ days for monthly)
        $overdueSubs = MemberSubscription::where('status', 'active')
            ->where('end_time', '<', Carbon::now())
            ->get();

        foreach ($overdueSubs as $sub) {
            $sub->update(['status' => 'expired']);
            if ($sub->member && $sub->member->membership_status !== 'suspended') {
                $sub->member->update(['membership_status' => 'expired']);
            }
        }

        $members = Member::with(['customer', 'latestSubscription.package'])->get();
        $packages = Package::where('is_active', true)->get();

        return view('owner.members', compact('members', 'packages'));
    }

    // Owner registers member: Enrolls member, attaches pass, logs payment & revenue, awards points
    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'nullable|string|min:6',
            'contact_number' => 'required|string|max:30',
            'date_of_birth'  => 'nullable|date',
            'package_id'     => 'nullable|exists:packages,id',
            'address'        => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request) {
            $rawPassword = $request->filled('password') ? $request->password : 'pass123';

            // 1. Create User
            $user = User::create([
                'email'             => strtolower($request->email),
                'password'          => Hash::make($rawPassword),
                'role'              => 'member',
                'account_status'    => 'active',
                'email_verified_at' => Carbon::now(),
            ]);

            // 2. Create Customer Profile
            $dob = $request->date_of_birth ? Carbon::parse($request->date_of_birth) : null;
            $customer = Customer::create([
                'user_id'                 => $user->id,
                'first_name'              => $request->first_name,
                'last_name'               => $request->last_name,
                'email'                   => strtolower($request->email),
                'contact_number'          => $request->contact_number,
                'date_of_birth'           => $request->date_of_birth,
                'age'                     => $dob ? $dob->age : null,
                'address'                 => $request->address ?? 'Toril, Davao City',
            ]);

            $count = Member::count() + 1;
            $memberCode = 'ECO-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $hasActivePackage = $request->filled('package_id');
            $package = $hasActivePackage ? Package::find($request->package_id) : null;

            // 3. Determine initial reward points
            $initialPoints = 0;
            if ($package) {
                if ($package->plan_type === 'daily' || $package->duration_in_days <= 1) {
                    $initialPoints = 3;
                } elseif ($package->duration_in_days >= 365) {
                    $initialPoints = 180;
                } elseif ($package->duration_in_days >= 90) {
                    $initialPoints = 45;
                } else {
                    $initialPoints = 15;
                }
            }

            // 4. Create Member Record
            $member = Member::create([
                'customer_id'         => $customer->id,
                'member_code'         => $memberCode,
                'joined_date'         => Carbon::today(),
                'verification_status' => 'verified',
                'membership_status'   => $hasActivePackage ? 'active' : 'expired',
                'reward_points'       => $initialPoints,
            ]);

            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            // 5. If a pass was chosen, create subscription, record verified payment & recognized revenue
            if ($hasActivePackage && $package) {
                $isDaily = ($package->plan_type === 'daily' || $package->duration_in_days <= 1);
                
                // Daily passes expire strictly at the end of the day (23:59:59)
                $startTime = Carbon::now();
                $endTime = $isDaily ? Carbon::today()->endOfDay() : Carbon::now()->addDays($package->duration_in_days);

                $sub = MemberSubscription::create([
                    'member_id'  => $member->id,
                    'package_id' => $package->id,
                    'start_time' => $startTime,
                    'end_time'   => $endTime,
                    'status'     => 'active'
                ]);

                // Record Payment
                $cashMethod = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Physical / Walk-In']);
                $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');

                $payment = Payment::create([
                    'payment_code'           => $paymentCode,
                    'customer_id'            => $customer->id,
                    'member_id'              => $member->id,
                    'member_subscription_id' => $sub->id,
                    'payment_method_id'      => $cashMethod->id,
                    'amount'                 => $package->price,
                    'payment_type'           => $isDaily ? 'per_session' : 'monthly_subscription',
                    'status'                 => 'verified',
                    'verified_at'            => Carbon::now(),
                    'verified_by'            => auth()->id()
                ]);

                // Record Revenue to update Total Revenue counter
                $budOps = Budget::firstOrCreate(
                    ['budget_code' => 'BUD-2026-OPS'],
                    ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00]
                );

                Revenue::create([
                    'revenue_code' => 'REV-' . $payment->id,
                    'payment_id'   => $payment->id,
                    'budget_id'    => $budOps->id,
                    'amount'       => $package->price,
                    'revenue_date' => Carbon::today()->toDateString()
                ]);
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "New Member Enrolled ({$memberCode})" . ($package ? " with {$package->name} (₱{$package->price} Revenue Recorded)" : ""),
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => $package ? ($package->plan_type === 'daily' ? 'End of Day' : "{$package->duration_in_days} Days") : 'No Pass',
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', 'Member registered, subscription activated, and revenue recorded successfully.');
    }

    public function approve(Member $member)
    {
        DB::transaction(function () use ($member) {
            $member->update([
                'verification_status' => 'verified'
            ]);

            if ($member->customer && $member->customer->user) {
                $member->customer->user->update(['account_status' => 'active']);
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Member Account Verified & Approved ({$member->member_code})",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => 'Verified Account',
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', "Member {$member->member_code} is now VERIFIED.");
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'contact_number'      => 'nullable|string|max:30',
            'verification_status' => 'required|in:pending,verified',
            'membership_status'   => 'nullable|in:active,expired,suspended',
            'suspension_reason'   => 'nullable|required_if:membership_status,suspended|string|max:255',
            'end_date'            => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $member) {
            if ($member->customer) {
                $member->customer->update([
                    'first_name'     => $request->first_name,
                    'last_name'      => $request->last_name,
                    'contact_number' => $request->contact_number,
                ]);
            }

            $sub = $member->latestSubscription;
            $package = $sub ? $sub->package : null;
            $canModifyPass = $sub && 
                             $sub->status === 'active' && 
                             $package && 
                             ($package->duration_in_days >= 28 || in_array($package->plan_type, ['monthly', 'quarterly', 'yearly', 'annual']));

            $updateData = [
                'verification_status' => $request->verification_status,
            ];

            if ($request->filled('membership_status')) {
                if ($request->membership_status === 'suspended' || $canModifyPass) {
                    $updateData['membership_status'] = $request->membership_status;
                    $updateData['suspension_reason'] = $request->membership_status === 'suspended' ? $request->suspension_reason : null;
                }
            }

            $member->update($updateData);

            if ($member->customer && $member->customer->user) {
                $member->customer->user->update([
                    'account_status' => $request->verification_status === 'verified' ? 'active' : 'pending'
                ]);
            }

            if ($canModifyPass && $request->filled('end_date') && $sub) {
                $sub->update([
                    'end_time' => Carbon::parse($request->end_date)->endOfDay(),
                    'status'   => Carbon::parse($request->end_date)->isPast() ? 'expired' : 'active',
                ]);
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Member Profile Updated ({$member->member_code}) - Verified: {$request->verification_status}",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', "Member {$member->member_code} details updated successfully.");
    }
}