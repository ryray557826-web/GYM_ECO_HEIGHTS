@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between">
            <span>✓ {{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-950/80 border border-rose-800 text-rose-300 px-4 py-3 rounded-xl text-xs font-semibold">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Profile Header Banner -->
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-2xl bg-[#080d1a] border border-[#1e293b] flex items-center justify-center font-heading text-2xl font-bold text-[#76c800]">
                {{ strtoupper(substr($user->isOwner() ? ($employee->first_name ?? 'Owner') : ($customer->first_name ?? 'Member'), 0, 1)) }}
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-xl font-bold text-white">
                        {{ $user->isOwner() ? ($employee ? $employee->full_name : 'Gym Owner') : ($customer ? $customer->full_name : 'Member Profile') }}
                    </h2>
                    <span class="px-2.5 py-0.5 rounded text-[10px] font-bold uppercase 
                        @if($user->isOwner()) bg-purple-950 text-purple-300 border border-purple-800/40
                        @elseif(isset($member) && $member->membership_status === 'active') bg-emerald-950 text-[#76c800] border border-[#76c800]/40
                        @else bg-amber-950 text-amber-400 border border-amber-800/40 @endif">
                        {{ $user->isOwner() ? 'ADMINISTRATOR' : ($member ? strtoupper($member->membership_status) : 'MEMBER') }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ $user->email }}</p>
            </div>
        </div>

        <div>
            @if($user->isOwner())
                <a href="{{ route('owner.checkin') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs uppercase font-heading font-semibold transition">
                    ← Back to Dashboard
                </a>
            @else
                <a href="{{ route('member.overview') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs uppercase font-heading font-semibold transition">
                    ← Back to Overview
                </a>
            @endif
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left Column: Identity Overview Card -->
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-4">
            <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-slate-400">ACCOUNT DETAILS</h3>
            
            <div class="space-y-3 text-xs">
                @if($user->isOwner())
                    <div>
                        <span class="text-slate-500 block">Employee Code</span>
                        <span class="font-mono text-white font-bold">{{ $employee->employee_code ?? 'EMP-001' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Position</span>
                        <span class="text-white font-semibold uppercase">{{ $employee->position ?? 'Owner' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Hire Date</span>
                        <span class="font-mono text-white">{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : 'Nov 18, 2025' }}</span>
                    </div>
                @else
                    <div>
                        <span class="text-slate-500 block">Member ID</span>
                        <span class="font-mono text-[#76c800] font-bold text-sm">{{ $member->member_code ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Current Plan</span>
                        <span class="text-white font-semibold">
                            {{ $member && $member->latestSubscription && $member->latestSubscription->package ? $member->latestSubscription->package->name : 'No Active Pass' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Pass Expiry</span>
                        <span class="font-mono text-white">
                            {{ $member && $member->latestSubscription ? $member->latestSubscription->end_time->format('M d, Y H:i') : 'N/A' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Reward Points</span>
                        <span class="font-mono text-[#76c800] font-bold text-sm">{{ $member->reward_points ?? 0 }} PTS</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block">Member Since</span>
                        <span class="font-mono text-white">{{ $member && $member->joined_date ? $member->joined_date->format('M d, Y') : 'N/A' }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Editable Profile & Password Form -->
        <div class="md:col-span-2 bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-6">
            <div>
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-slate-400">UPDATE INFORMATION</h3>
                <p class="text-xs text-slate-400 mt-0.5">Manage your personal contact details and password.</p>
            </div>

            <form action="{{ route('profile.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-slate-400 mb-1">Contact Phone</label>
                        <input type="text" name="contact_number" 
                            value="{{ old('contact_number', $user->isOwner() ? ($employee->contact_number ?? '') : ($customer->contact_number ?? '')) }}"
                            class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white font-mono focus:border-[#76c800] outline-none">
                    </div>

                    @if(!$user->isOwner())
                    <div>
                        <label class="block text-slate-400 mb-1">Emergency Contact Phone</label>
                        <input type="text" name="emergency_contact_phone" 
                            value="{{ old('emergency_contact_phone', $customer->emergency_contact_phone ?? '') }}"
                            class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white font-mono focus:border-[#76c800] outline-none">
                    </div>
                    @endif
                </div>

                @if(!$user->isOwner())
                <div>
                    <label class="block text-slate-400 mb-1">Home Address</label>
                    <textarea name="address" rows="2" 
                        class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white focus:border-[#76c800] outline-none">{{ old('address', $customer->address ?? '') }}</textarea>
                </div>
                @endif

                <!-- Password Reset Section -->
                <div class="border-t border-[#1e293b] pt-4 space-y-3">
                    <h4 class="text-xs font-heading font-bold uppercase tracking-wider text-white">CHANGE PASSWORD (OPTIONAL)</h4>
                    
                    <div>
                        <label class="block text-slate-400 mb-1">Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password to verify"
                            class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white focus:border-[#76c800] outline-none">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-400 mb-1">New Password</label>
                            <input type="password" name="new_password" placeholder="At least 6 characters"
                                class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white focus:border-[#76c800] outline-none">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" placeholder="Repeat new password"
                                class="w-full bg-[#080d1a] border border-[#1e293b] rounded-xl p-2.5 text-white focus:border-[#76c800] outline-none">
                        </div>
                    </div>
                </div>

                <div class="pt-2 text-right">
                    <button type="submit" 
                        class="px-5 py-2.5 bg-[#76c800] hover:bg-[#68b000] text-slate-950 font-extrabold text-xs uppercase font-heading rounded-xl shadow-lg shadow-[#76c800]/20 transition">
                        Save Profile Changes
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>
@endsection