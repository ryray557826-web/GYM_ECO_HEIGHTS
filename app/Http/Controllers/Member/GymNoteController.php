<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\GymNote;
use App\Models\Customer;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GymNoteController extends Controller
{
    // The missing index method:
    public function index()
    {
        $user = Auth::user();
        $member = null;

        if ($user->customer && $user->customer->member) {
            $member = $user->customer->member;
        }

        if (!$member) {
            $customer = Customer::where('user_id', $user->id)
                                ->orWhere('email', $user->email)
                                ->first();
            if ($customer) {
                $member = $customer->member;
            }
        }

        if (!$member && isset($user->member_id)) {
            $member = Member::where('member_code', $user->member_id)->first();
        }

        $gymNotes = $member ? $member->gymNotes : collect();

        return view('member.gym-notes', compact('member', 'gymNotes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'workout_date' => 'required|date',
            'routine_title' => 'required|string|max:150',
            'notes' => 'required|string',
        ]);

        $user = Auth::user();
        $member = null;

        if ($user->customer && $user->customer->member) {
            $member = $user->customer->member;
        }

        if (!$member) {
            $customer = Customer::where('user_id', $user->id)
                                ->orWhere('email', $user->email)
                                ->first();
            if ($customer) {
                $member = $customer->member;
            }
        }

        if (!$member && isset($user->member_id)) {
            $member = Member::where('member_code', $user->member_id)->first();
        }

        if (!$member) {
            return back()->withErrors(['error' => 'No active member profile linked to this account.']);
        }

        GymNote::create([
            'member_id' => $member->id,
            'attendance_id' => $request->attendance_id,
            'workout_date' => $request->workout_date,
            'routine_title' => $request->routine_title,
            'notes' => $request->notes,
        ]);

        return back()->with('success', 'Workout routine note saved.');
    }

    public function destroy(GymNote $gymNote)
    {
        $user = Auth::user();
        $member = null;

        if ($user->customer && $user->customer->member) {
            $member = $user->customer->member;
        }

        if ($member && $gymNote->member_id !== $member->id) {
            abort(403, 'Unauthorized action.');
        }

        $gymNote->delete();
        return back()->with('success', 'Workout note deleted.');
    }
}