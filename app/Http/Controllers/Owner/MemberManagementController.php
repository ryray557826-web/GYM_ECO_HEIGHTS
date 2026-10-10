<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
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
        // Automatically mark unrenewed subscriptions whose validity has passed as expired
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

    // Owner directly adds an APPROVED member
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

            $user = User::create([
                'email'             => strtolower($request->email),
                'password'          => Hash::make($rawPassword),
                'role'              => 'member',
                'account_status'    => 'active',
                'email_verified_at' => Carbon::now(),
            ]);

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

            $member = Member::create([
                'customer_id'         => $customer->id,
                'member_code'         => $memberCode,
                'joined_date'         => Carbon::today(),
                'verification_status' => 'verified', // Directly Verified
                'membership_status'   => $hasActivePackage ? 'active' : 'expired',
                'reward_points'       => 0,
            ]);

            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            if ($hasActivePackage) {
                $package = Package::find($request->package_id);
                MemberSubscription::create([
                    'member_id'  => $member->id,
                    'package_id' => $package->id,
                    'start_time' => Carbon::now(),
                    'end_time'   => Carbon::now()->addDays($package->duration_in_days),
                    'status'     => 'active'
                ]);
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "New Member Enrolled and Verified ({$memberCode})",
                    'entity_type'     => Member::class,
                    'entity_id'       => $member->id,
                    'validity_period' => $hasActivePackage ? 'Active Plan' : 'No Active Pass',
                    'performed_by'    => 'Owner'
                ]);
            }
        });

        return back()->with('success', 'Member created and verified successfully.');
    }

    // 1-Click Approve pending member to VERIFIED
    public function approve(Member $member)
    {
        DB::transaction(function () use ($member) {
            $member->update([
                'verification_status' => 'verified'
            ]);

            if ($member->customer && $member->customer->user) {
                $member->customer->user->update(['account_status' => 'active']);
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Member Account Verified & Approved ({$member->member_code})",
                    'entity_type'     => Member::class,
                    'entity_id'       => $member->id,
                    'validity_period' => 'Verified Account',
                    'performed_by'    => 'Owner'
                ]);
            }
        });

        return back()->with('success', "Member {$member->member_code} is now VERIFIED.");
    }

    // Edit Member Profile, Verification Status, Pass Status, & Suspension Reason
    public function update(Request $request, Member $member)
    {
        $request->validate([
            'first_name'          => 'required|string|max:100',
            'last_name'           => 'required|string|max:100',
            'contact_number'      => 'nullable|string|max:30',
            'verification_status' => 'required|in:pending,verified',
            'membership_status'   => 'required|in:active,expired,suspended',
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

            $member->update([
                'verification_status' => $request->verification_status,
                'membership_status'   => $request->membership_status,
                'suspension_reason'   => $request->membership_status === 'suspended' ? $request->suspension_reason : null,
            ]);

            if ($member->customer && $member->customer->user) {
                $member->customer->user->update([
                    'account_status' => $request->verification_status === 'verified' ? 'active' : 'pending'
                ]);
            }

            if ($request->filled('end_date') && $member->latestSubscription) {
                $member->latestSubscription->update([
                    'end_time' => Carbon::parse($request->end_date)->endOfDay(),
                    'status'   => Carbon::parse($request->end_date)->isPast() ? 'expired' : 'active',
                ]);
            }

            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Member Profile Updated ({$member->member_code}) - Verified: {$request->verification_status} | Pass: {$request->membership_status}" . ($request->membership_status === 'suspended' ? " | Reason: {$request->suspension_reason}" : ""),
                    'entity_type'     => Member::class,
                    'entity_id'       => $member->id,
                    'performed_by'    => 'Owner'
                ];
                AuditLog::create($auditData);
            }
        });

        return back()->with('success', "Member {$member->member_code} details updated successfully.");
    }
}