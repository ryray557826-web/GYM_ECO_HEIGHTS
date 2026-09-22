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
            'password' => 'required|string',
        ]);

        $input = trim($request->identifier);

        // 1. Direct search by email
        $user = User::where('email', strtolower($input))->first();

        // 2. Search by member_id on users table (if column exists)
        if (!$user && Schema::hasColumn('users', 'member_id')) {
            $user = User::whereRaw('UPPER(member_id) = ?', [strtoupper($input)])->first();
        }

        // 3. Search via 3NF members table (member_code e.g. ECO-001)
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
                'identifier' => "Account [{$input}] was not found in the gym database."
            ])->withInput();
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'identifier' => 'Incorrect password entered.'
            ])->withInput();
        }

        // Owner accounts are always verified and active
        if ($user->isOwner()) {
            if (Schema::hasColumn('users', 'account_status')) {
                $user->account_status = 'active';
            } elseif (Schema::hasColumn('users', 'status')) {
                $user->status = 'active';
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $user->email_verified_at = now();
            }
            $user->save();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Redirect to dedicated portal pages
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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'contact_number' => 'required|string|max:30',
            'date_of_birth' => 'required|date',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'address' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Create Auth User safely
            $userData = [
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
            ];

            if (Schema::hasColumn('users', 'name')) {
                $userData['name'] = "{$request->first_name} {$request->last_name}";
            }
            if (Schema::hasColumn('users', 'role')) {
                $userData['role'] = 'member';
            }
            if (Schema::hasColumn('users', 'account_status')) {
                $userData['account_status'] = 'pending';
            } elseif (Schema::hasColumn('users', 'status')) {
                $userData['status'] = 'pending';
            }

            $user = User::create($userData);

            // 2. Create Customer Profile
            $dob = Carbon::parse($request->date_of_birth);
            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => strtolower($request->email),
                'contact_number' => $request->contact_number,
                'date_of_birth' => $request->date_of_birth,
                'age' => $dob->age,
                'emergency_contact_name' => $request->emergency_contact_name ?? 'N/A',
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'address' => $request->address,
            ]);

            // 3. Auto-generate sequential Member Code: ECO-001, ECO-002...
            $count = Member::count() + 1;
            $memberCode = 'ECO-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $memberData = [
                'customer_id' => $customer->id,
                'member_code' => $memberCode,
                'joined_date' => Carbon::today()->toDateString(),
            ];

            if (Schema::hasColumn('members', 'membership_status')) {
                $memberData['membership_status'] = 'pending';
            } elseif (Schema::hasColumn('members', 'status')) {
                $memberData['status'] = 'pending';
            }

            $member = Member::create($memberData);

            // Sync to users table if column exists
            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            // 4. Log to Audit Trail
            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code' => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id' => $user->id,
                    'action' => "New Member Registration ({$memberCode})",
                    'performed_by' => 'Self / Online',
                ];
                if (Schema::hasColumn('audit_logs', 'validity_period')) $auditData['validity_period'] = 'Pending Approval';
                if (Schema::hasColumn('audit_logs', 'validity')) $auditData['validity'] = 'Pending Approval';
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Member::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $member->id;

                AuditLog::create($auditData);
            }

            Auth::login($user);

            return redirect()->route('member.overview')->with(
                'success',
                "Registration complete! Your Member ID is {$memberCode}. Your account is pending Owner verification."
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