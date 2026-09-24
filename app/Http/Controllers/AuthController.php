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

        $user = User::where('email', strtolower($input))->first();

        if (!$user && Schema::hasColumn('users', 'member_id')) {
            $user = User::whereRaw('UPPER(member_id) = ?', [strtoupper($input)])->first();
        }

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

        if ($user->isOwner()) {
            if (Schema::hasColumn('users', 'account_status')) $user->account_status = 'active';
            if (Schema::hasColumn('users', 'status')) $user->status = 'active';
            if (Schema::hasColumn('users', 'email_verified_at')) $user->email_verified_at = now();
            $user->save();
        }

        // Remembers user session across browser restarts
        $remember = $request->boolean('remember');
        Auth::login($user, $remember);
        $request->session()->regenerate();

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
            'password' => 'required|string|min:6',
            'contact_number' => 'required|string|max:30',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
        ]);

        return DB::transaction(function () use ($request) {
            $userData = [
                'email' => strtolower($request->email),
                'password' => Hash::make($request->password),
            ];
            if (Schema::hasColumn('users', 'name')) $userData['name'] = "{$request->first_name} {$request->last_name}";
            if (Schema::hasColumn('users', 'role')) $userData['role'] = 'member';
            if (Schema::hasColumn('users', 'account_status')) $userData['account_status'] = 'active';

            $user = User::create($userData);

            $dob = Carbon::parse($request->date_of_birth);
            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => strtolower($request->email),
                'contact_number' => $request->contact_number,
                'date_of_birth' => $request->date_of_birth,
                'age' => $dob->age,
                'emergency_contact_phone' => $request->emergency_contact_phone,
                'address' => $request->address,
            ]);

            $count = Member::count() + 1;
            $memberCode = 'ECO-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            Member::create([
                'customer_id' => $customer->id,
                'member_code' => $memberCode,
                'joined_date' => Carbon::today(),
                'membership_status' => 'active',
            ]);

            if (Schema::hasColumn('users', 'member_id')) {
                $user->member_id = $memberCode;
                $user->save();
            }

            // Always login with remember token true on registration
            Auth::login($user, true);

            return redirect()->route('member.overview')->with(
                'success',
                "Welcome {$request->first_name}! Your Member ID is {$memberCode}."
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