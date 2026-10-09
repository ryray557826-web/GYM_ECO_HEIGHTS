@extends('layouts.app')

@section('content')
<div class="space-y-6 sm:space-y-8">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-lg">
            <span>✓ {{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
        </div>
    @endif
    @if(session('info'))
        <div class="bg-amber-950/80 border border-amber-500/40 text-amber-300 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-lg">
            <span>ℹ️ {{ session('info') }}</span>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-950/80 border border-rose-800 text-rose-300 px-4 py-3 rounded-xl text-xs font-semibold shadow-lg">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- PENDING ACCOUNT BANNER -->
    @if($member->membership_status === 'pending' || auth()->user()->account_status === 'pending')
    <div class="bg-amber-950/60 border border-amber-500/40 p-4 rounded-2xl flex items-center justify-between shadow-lg">
        <div class="flex items-center space-x-3">
            <span class="text-amber-400 text-2xl">⏳</span>
            <div>
                <h4 class="text-xs font-bold text-amber-400 uppercase font-heading tracking-wider">Account Pending Owner Verification</h4>
                <p class="text-xs text-slate-300 mt-0.5">
                    Your account has been registered. You can only apply for a <strong>Daily Pass (1 day)</strong> until verified by the owner.
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Member Top Tabs (Fluid Wide Nav) -->
    <div class="border-b border-slate-800 pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
        <nav class="flex space-x-8 text-xs font-heading font-bold uppercase tracking-wider overflow-x-auto no-scrollbar whitespace-nowrap">
            <a href="{{ route('member.overview') }}" class="py-3 text-emerald-400 border-b-2 border-emerald-400 text-sm">
                Overview
            </a>
            <a href="{{ route('member.notes.index') }}" class="py-3 text-slate-400 hover:text-slate-200 border-b-2 border-transparent text-sm flex items-center space-x-2">
                <span>Gym Notes</span>
                <span class="bg-slate-800 text-white text-[10px] px-2 py-0.5 rounded font-mono">{{ $member->gymNotes->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- 1. TOP HERO STATUS & REWARD POINTS GRID (3 Expansive Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">
        
        <!-- Card 1: Membership Status -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center">
                    <span class="text-xs uppercase font-heading font-semibold text-slate-400 tracking-wider">MEMBERSHIP STATUS</span>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded 
                        @if($member->membership_status === 'active') bg-emerald-950 text-emerald-400 border border-emerald-500/30
                        @elseif($member->membership_status === 'expired') bg-rose-950 text-rose-400 border border-rose-800/30
                        @else bg-amber-950 text-amber-400 border border-amber-800/30 @endif">
                        {{ strtoupper($member->membership_status ?? 'PENDING') }}
                    </span>
                </div>
                <div class="mt-3 text-lg sm:text-xl font-heading font-extrabold text-white">
                    {{ $member->latestSubscription && $member->latestSubscription->package ? $member->latestSubscription->package->name : 'No Active Pass' }}
                </div>
            </div>
            <div class="pt-4 border-t border-slate-800 flex justify-between text-xs text-slate-400 font-mono">
                <span>Member ID: <strong class="text-white">{{ $member->member_code }}</strong></span>
                <span>Joined: {{ $member->joined_date ? $member->joined_date->format('M d, Y') : '2026-08-18' }}</span>
            </div>
        </div>

        <!-- Card 2: Attendance & Payment Reward Points (Goal: 500 PTS) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md flex flex-col justify-between space-y-4">
            <div>
                <div class="flex justify-between items-center">
                    <span class="text-xs uppercase font-heading font-semibold text-slate-400 tracking-wider">REWARD POINTS</span>
                    <span class="text-emerald-400 font-mono font-bold text-sm bg-emerald-950/80 px-2.5 py-0.5 rounded border border-emerald-500/30">
                        {{ $member->reward_points ?? 0 }} / 500 PTS
                    </span>
                </div>

                <div class="w-full bg-slate-950 border border-slate-800 rounded-full h-2 mt-3 overflow-hidden">
                    <div class="bg-emerald-400 h-2 rounded-full transition-all duration-500" 
                         style="width: {{ min(100, (($member->reward_points ?? 0) / 500) * 100) }}%"></div>
                </div>

                <p class="text-[11px] text-slate-400 mt-2">
                    Daily: <span class="text-white font-semibold">+3 pts</span> · Monthly: <span class="text-emerald-400 font-semibold">+15 pts</span>.
                </p>
            </div>

            @if(($member->reward_points ?? 0) >= 500)
                <form action="{{ route('member.redeemPoints') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold text-xs uppercase font-heading rounded-lg shadow-lg transition tracking-wider">
                        🎁 Redeem 500 PTS for 1 Month Free Access
                    </button>
                </form>
            @else
                <div class="text-[10px] text-slate-500 font-mono flex items-center justify-between border-t border-slate-800 pt-2">
                    <span>Target: 500 PTS</span>
                    <span>Remaining: {{ max(0, 500 - ($member->reward_points ?? 0)) }} PTS</span>
                </div>
            @endif
        </div>

        <!-- Card 3: Expiration & Workouts -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="text-xs uppercase font-heading font-semibold text-slate-400 tracking-wider">PASS EXPIRATION</span>
                <div class="text-lg sm:text-xl font-heading font-extrabold text-emerald-400 mt-2 font-mono">
                    {{ $member->latestSubscription ? $member->latestSubscription->end_time->format('Y-m-d H:i') : 'No Active Pass' }}
                </div>
            </div>
            <div class="pt-4 border-t border-slate-800 flex justify-between items-center">
                <span class="text-xs text-slate-400">Total Workouts: <strong class="text-white font-mono text-sm">{{ $member->gymNotes->count() }}</strong></span>
                <a href="{{ route('member.notes.index') }}" class="text-xs text-emerald-400 hover:underline font-heading font-semibold">
                    View Logs →
                </a>
            </div>
        </div>

    </div>

    <!-- 2. FLUID TWO-COLUMN WORKSPACE -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
        
        <!-- LEFT COLUMN: ANNOUNCEMENTS & ATTENDANCE -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Tabular: Live Announcements Board -->
            @php
                $memberAnnouncements = \App\Models\Announcement::latest('posted_date')->take(6)->get();
            @endphp
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span>📢</span><span>GYM ANNOUNCEMENTS BOARD</span>
                    </h3>
                    <span class="text-xs font-mono text-slate-400">{{ $memberAnnouncements->count() }} Updates</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-3">DATE</th>
                                <th class="p-3">TAG</th>
                                <th class="p-3">ANNOUNCEMENT DETAILS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300">
                            @forelse($memberAnnouncements as $anc)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="p-3 font-mono text-slate-400 whitespace-nowrap">{{ $anc->posted_date->format('M d, Y') }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                        @if($anc->badge === 'IMPORTANT') bg-rose-950 text-rose-400 border border-rose-800/40
                                        @elseif($anc->badge === 'SCHEDULE') bg-sky-950 text-sky-400 border border-sky-800/40
                                        @elseif($anc->badge === 'PROMO') bg-emerald-950 text-emerald-400 border border-emerald-500/40
                                        @else bg-slate-800 text-slate-300 @endif">
                                        {{ $anc->badge }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <strong class="text-white block text-sm">{{ $anc->title }}</strong>
                                    <p class="text-slate-300 text-xs mt-0.5 leading-relaxed">{{ $anc->message }}</p>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-slate-500 font-sans italic">
                                    No announcements published right now.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Gym Visit History -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span>🗓️</span><span>GYM VISIT LOG SHEET</span>
                    </h3>
                    <span class="text-xs font-mono text-slate-400">Total Visits: {{ $member->attendances->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-3">VISIT DATE</th>
                                <th class="p-3">TIME IN</th>
                                <th class="p-3">PASS TYPE</th>
                                <th class="p-3 text-right">POINTS CREDITED</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300">
                            @forelse($member->attendances as $att)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="p-3 font-bold text-white">{{ \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d') }}</td>
                                <td class="p-3 text-slate-400">{{ $att->check_in_time }}</td>
                                <td class="p-3 font-sans">
                                    @if($att->entry_type === 'membership')
                                        <span class="text-emerald-400 font-semibold">Monthly Member Pass</span>
                                    @else
                                        <span class="text-amber-400 font-semibold">Per-Session Entry (₱50)</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-sans">
                                    <span class="text-emerald-400 font-bold font-mono">
                                        {{ $att->entry_type === 'membership' ? 'Active Pass' : '+3 PTS' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-slate-500 font-sans italic">No gym visits recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PAYMENT ACTION & REQUESTS -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Quick Renewal Trigger Card -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-heading font-extrabold text-white uppercase tracking-wider">Membership Plan</h4>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($member->membership_status === 'pending')
                            Apply for a Daily Pass today.
                        @else
                            Renew monthly, quarterly, or yearly online.
                        @endif
                    </p>
                </div>
                <button onclick="document.getElementById('paymentModal').showModal()" 
                    class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold px-4 sm:px-5 py-2.5 rounded-xl text-xs uppercase font-heading shadow-lg shadow-emerald-500/20 transition flex items-center space-x-1.5">
                    <span>💳</span><span>Make Payment</span>
                </button>
            </div>

            <!-- Tabular: Payment Requests -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md space-y-4">
                <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">PAYMENT REQUESTS</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-2.5">PLAN</th>
                                <th class="p-2.5">AMOUNT</th>
                                <th class="p-2.5">REF NO.</th>
                                <th class="p-2.5 text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 font-mono text-slate-300">
                            @forelse($member->payments as $p)
                            <tr>
                                <td class="p-2.5 font-sans font-semibold text-white">
                                    {{ $p->payment_type === 'monthly_subscription' ? 'Subscription' : 'Daily Pass' }}
                                </td>
                                <td class="p-2.5 text-emerald-400 font-bold">₱{{ number_format($p->amount, 2) }}</td>
                                <td class="p-2.5 text-slate-300 text-[11px]">{{ $p->reference_number ?? '—' }}</td>
                                <td class="p-2.5 text-right font-sans">
                                    @if($p->status === 'verified')
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-950 text-emerald-400">APPROVED</span>
                                    @elseif($p->status === 'rejected')
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-rose-950 text-rose-400">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-amber-950 text-amber-400">PENDING</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-3 text-center text-slate-500 font-sans italic">No payment requests submitted yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Payment Receipts History -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 sm:p-6 shadow-md space-y-4">
                <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">PAYMENT HISTORY</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-2.5">CODE</th>
                                <th class="p-2.5">DATE</th>
                                <th class="p-2.5 text-right">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 text-slate-300">
                            @forelse($member->payments->where('status', 'verified') as $pay)
                            <tr>
                                <td class="p-2.5 text-slate-400">{{ $pay->payment_code }}</td>
                                <td class="p-2.5">{{ $pay->created_at->format('Y-m-d') }}</td>
                                <td class="p-2.5 text-right text-emerald-400 font-bold">₱{{ number_format($pay->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-3 text-center text-slate-500 font-sans italic">No verified receipts yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal: Membership Payment (Restricted to 1-Day Pass if Pending) -->
<dialog id="paymentModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">
            {{ ($member->membership_status === 'pending' || auth()->user()->account_status === 'pending') ? 'Apply for Daily Pass (1 Day)' : 'Renew Membership Subscription' }}
        </h3>
        <button onclick="document.getElementById('paymentModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    
    <form action="{{ route('member.paySubscription') }}" method="POST" class="space-y-4 text-xs">
        @csrf
        
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Select Membership Plan *</label>
            <select name="package_id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-medium focus:border-emerald-500 outline-none">
                @if($member->membership_status === 'pending' || auth()->user()->account_status === 'pending')
                    <!-- PENDING ACCOUNTS: RESTRICTED TO DAILY SUBSCRIPTION ONLY -->
                    @foreach($packages->where('plan_type', 'daily') as $pkg)
                        <option value="{{ $pkg->id }}">
                            {{ $pkg->name }} — ₱{{ number_format($pkg->price) }} (1-Day Access)
                        </option>
                    @endforeach
                @else
                    <!-- VERIFIED ACCOUNTS: ALL PLANS ACCESSIBLE -->
                    @foreach($packages->where('plan_type', '!=', 'daily') as $pkg)
                        <option value="{{ $pkg->id }}">
                            {{ $pkg->name }} — ₱{{ number_format($pkg->price) }}
                            @if($pkg->promo_badge) ({{ $pkg->promo_badge }}) @endif
                        </option>
                    @endforeach
                @endif
            </select>
            @if($member->membership_status === 'pending')
                <p class="text-[10px] text-amber-400 mt-1">
                    * Pending accounts can only purchase 1-day daily access until verified by the owner.
                </p>
            @endif
        </div>

        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Online Payment Channel *</label>
            <select name="payment_method_id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-medium focus:border-emerald-500 outline-none">
                @foreach($paymentMethods->where('code', '!=', 'cash') as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }} (Online Transfer)</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Online Reference Number *</label>
            <input type="text" name="reference_number" required placeholder="e.g. Maya-20260910-77341 / GCash Ref" 
                class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-mono focus:border-emerald-500 outline-none">
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('paymentModal').close()" class="px-3.5 py-2 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-lg uppercase font-heading transition shadow-lg shadow-emerald-500/20">
                Submit Request
            </button>
        </div>
    </form>
</dialog>
@endsection