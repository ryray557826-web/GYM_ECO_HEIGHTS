<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if ($user->isOwner()) {
            $employee = $user->employee ?? Employee::where('user_id', $user->id)->first();
            return view('profile.show', compact('user', 'employee'));
        }

        $customer = $user->customer ?? Customer::where('user_id', $user->id)->first();
        $member = $customer ? $customer->member : null;

        if ($member) {
            $member->load(['latestSubscription.package']);
        }

        return view('profile.show', compact('user', 'customer', 'member'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'contact_number'          => 'nullable|string|max:30',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'address'                 => 'nullable|string|max:255',
            'current_password'        => 'nullable|string',
            'new_password'            => 'nullable|string|min:6|confirmed',
        ]);

        DB::transaction(function () use ($request, $user) {
            // 1. Password change
            if ($request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'current_password' => 'The provided current password does not match our records.'
                    ]);
                }
                $user->password = Hash::make($request->new_password);
                $user->save();
            }

            // 2. Update contact details based on profile type
            if ($user->isOwner() && $user->employee) {
                $user->employee->update([
                    'contact_number' => $request->contact_number ?? $user->employee->contact_number,
                ]);
            } elseif ($user->customer) {
                $user->customer->update([
                    'contact_number'          => $request->contact_number ?? $user->customer->contact_number,
                    'emergency_contact_phone' => $request->emergency_contact_phone ?? $user->customer->emergency_contact_phone,
                    'address'                 => $request->address ?? $user->customer->address,
                ]);
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => $user->id,
                    'action'          => "Profile Information Updated",
                    'performed_by'    => $user->isOwner() ? 'Owner' : 'Member',
                    'validity_period' => 'Security Update',
                ]);
            }
        });

        return back()->with('success', 'Profile updated successfully.');
    }
}