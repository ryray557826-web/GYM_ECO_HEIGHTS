<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use App\Models\AuditLog;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $input = trim($request->identifier);

        // 1. Check direct user email match
        $user = User::where('email', strtolower($input))->first();

        // 2. Check users.member_id column if present
        if (!$user && Schema::hasColumn('users', 'member_id')) {
            $user = User::whereRaw('UPPER(member_id) = ?', [strtoupper($input)])->first();
        }

        // 3. Check members.member_code relation
        if (!$user) {
            $member = Member::whereRaw('UPPER(member_code) = ?', [strtoupper($input)])
                            ->with('customer.user')
                            ->first();

            if ($member && $member->customer && $member->customer->user) {
                $user = $member->customer->user;
            }
        }

        if (!$user) {
            return back()->withErrors([
                'identifier' => "No account found matching \"{$input}\"."
            ])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'identifier' => 'Incorrect password entered.'
            ])->withInput();
        }

        // Guarantee owner account status is active
        if ($user->isOwner()) {
            if (Schema::hasColumn('users', 'account_status')) $user->account_status = 'active';
            if (Schema::hasColumn('users', 'status')) $user->status = 'active';
            if (Schema::hasColumn('users', 'email_verified_at')) $user->email_verified_at = now();
            $user->save();
        }

        $remember = $request->boolean('remember');
        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Role-based transport
        if ($user->isOwner()) {
            return redirect()->intended(route('owner.checkin'));
        }

        return redirect()->intended(route('member.overview'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|string|min:6',
            'contact_number' => 'required|string|max:30',
            'date_of_birth'  => 'required|date',
            'address'        => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $userData = [
                'email'          => strtolower($request->email),
                'password'       => Hash::make($request->password),
                'role'           => 'member',
                'account_status' => 'pending', // Public registrations default to pending
            ];
            if (Schema::hasColumn('users', 'name')) $userData['name'] = "{$request->first_name} {$request->last_name}";

            $user = User::create($userData);

            $dob = Carbon::parse($request->date_of_birth);
            $customer = Customer::create([
                'user_id'                 => $user->id,
                'first_name'              => $request->first_name,
                'last_name'               => $request->last_name,
                'email'                   => strtolower($request->email),
                'contact_number'          => $request->contact_number,
                'date_of_birth'           => $request->date_of_birth,
                'age'                     => $dob->age,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'address'                 => $request->address,
            ]);

            $count = Member::count() + 1;
            $memberCode = 'ECO-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $member = Member::create([
                'customer_id'       => $customer->id,
                'member_code'       => $memberCode,
                'joined_date'       => Carbon::today(),
                'membership_status' => 'pending',
                'reward_points'     => 0,
            ]);

            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => $user->id,
                    'action'          => "New Member Self-Registered ({$memberCode}) - Awaiting Approval",
                    'performed_by'    => 'Public Registration',
                    'validity_period' => 'Pending Verification',
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Member::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $member->id;

                AuditLog::create($auditData);
            }

            Auth::login($user, true);

            return redirect()->route('member.overview')->with(
                'info',
                "Registration successful! Your Member ID is {$memberCode}. Your account is pending owner verification (you may apply for daily passes in the meantime)."
            );
        });
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}