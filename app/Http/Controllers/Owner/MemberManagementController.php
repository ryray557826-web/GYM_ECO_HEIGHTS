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
        $members = Member::with(['customer', 'latestSubscription.package'])->get();
        $packages = Package::where('is_active', true)->get();
        return view('owner.members', compact('members', 'packages'));
    }

    // Direct addition by Owner: Account is AUTO-APPROVED
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
            $user = User::create([
                'email'             => strtolower($request->email),
                'password'          => Hash::make($request->password ?? 'pass123'),
                'role'              => 'member',
                'account_status'    => 'active', // Pre-approved by Owner
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
                'address'                 => $request->address ?? 'Davao City',
            ]);

            $count = Member::count() + 1;
            $memberCode = 'ECO-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $member = Member::create([
                'customer_id'       => $customer->id,
                'member_code'       => $memberCode,
                'joined_date'       => Carbon::today(),
                'membership_status' => 'active', // Pre-approved by Owner
                'reward_points'     => 0,
            ]);

            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            if ($request->filled('package_id')) {
                $package = Package::find($request->package_id);
                MemberSubscription::create([
                    'member_id'  => $member->id,
                    'package_id' => $package->id,
                    'start_time' => Carbon::now(),
                    'end_time'   => Carbon::now()->addDays($package->duration_in_days),
                    'status'     => 'active'
                ]);
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "New Member Enrolled and Pre-Approved ({$memberCode})",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => 'Pre-Approved',
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', 'Member created and approved successfully.');
    }

    // 1-Click Approve pending member
    public function approve(Member $member)
    {
        DB::transaction(function () use ($member) {
            $member->update(['membership_status' => 'active']);
            if ($member->customer && $member->customer->user) {
                $member->customer->user->update(['account_status' => 'active']);
            }

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Pending Member Verified & Approved ({$member->member_code})",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => 'Approved',
                'performed_by'    => 'Owner'
            ]);
        });

        return back()->with('success', "Member {$member->member_code} is now verified and approved.");
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'contact_number'    => 'nullable|string|max:30',
            'membership_status' => 'required|in:active,expired,pending,suspended',
            'end_date'          => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $member) {
            if ($member->customer) {
                $member->customer->update([
                    'first_name'     => $request->first_name,
                    'last_name'      => $request->last_name,
                    'contact_number' => $request->contact_number,
                ]);
            }

            $member->update(['membership_status' => $request->membership_status]);

            if ($request->filled('end_date') && $member->latestSubscription) {
                $member->latestSubscription->update([
                    'end_time' => Carbon::parse($request->end_date)->endOfDay(),
                    'status'   => Carbon::parse($request->end_date)->isPast() ? 'expired' : 'active',
                ]);
            }
        });

        return back()->with('success', "Member {$member->member_code} updated.");
    }
}