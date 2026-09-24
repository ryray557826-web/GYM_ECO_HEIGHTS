@extends('layouts.app')

@section('content')
<div class="space-y-8">

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="bg-emerald-950/80 border border-[#76c800]/50 text-[#76c800] px-5 py-3 rounded-xl text-xs font-semibold flex items-center justify-between shadow-lg">
            <span class="flex items-center space-x-2">
                <span class="text-sm">✓</span>
                <span>{{ session('success') }}</span>
            </span>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-white">✕</button>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-950/80 border border-red-800 text-red-300 px-5 py-3 rounded-xl text-xs font-semibold shadow-lg">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Member Top Tabs (Fluid Wide Nav) -->
    <div class="border-b border-[#1e293b] pb-1">
        <nav class="flex space-x-10 text-xs font-heading font-bold uppercase tracking-wider">
            <a href="{{ route('member.overview') }}" class="py-3 text-[#76c800] border-b-2 border-[#76c800] text-sm">
                Overview
            </a>
            <a href="{{ route('member.notes.index') }}" class="py-3 text-gray-400 hover:text-gray-200 border-b-2 border-transparent text-sm flex items-center space-x-2">
                <span>Gym Notes</span>
                <span class="bg-[#1e293b] text-white text-[10px] px-2 py-0.5 rounded font-mono">{{ $member->gymNotes->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- 1. TOP HERO STATUS & REWARD POINTS GRID (3 Expansive Cards) -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Card 1: Membership Status -->
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <div class="flex justify-between items-center">
                    <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">MEMBERSHIP STATUS</span>
                    <span class="px-2.5 py-0.5 text-xs font-bold rounded 
                        @if($member->membership_status === 'active') bg-emerald-950 text-[#76c800] border border-[#76c800]/30
                        @elseif($member->membership_status === 'expired') bg-red-950 text-red-400 border border-red-800/30
                        @else bg-yellow-950 text-yellow-400 border border-yellow-800/30 @endif">
                        {{ strtoupper($member->membership_status ?? 'ACTIVE') }}
                    </span>
                </div>
                <div class="mt-3 text-xl font-heading font-extrabold text-white">
                    {{ $member->latestSubscription && $member->latestSubscription->package ? $member->latestSubscription->package->name : 'Monthly Standard Access' }}
                </div>
            </div>
            <div class="pt-4 border-t border-[#1e293b] flex justify-between text-xs text-gray-400 font-mono">
                <span>Member ID: <strong class="text-white">{{ $member->member_code }}</strong></span>
                <span>Joined: {{ $member->joined_date ? $member->joined_date->format('M d, Y') : '2026-08-18' }}</span>
            </div>
        </div>

        <!-- Card 2: Attendance & Payment Reward Points (Goal: 500 PTS) -->
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md flex flex-col justify-between space-y-4">
            <div>
                <div class="flex justify-between items-center">
                    <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">REWARD POINTS</span>
                    <span class="text-[#76c800] font-mono font-bold text-sm bg-emerald-950/80 px-2.5 py-0.5 rounded border border-[#76c800]/30">
                        {{ $member->reward_points ?? 0 }} / 500 PTS
                    </span>
                </div>

                <!-- Animated Progress Bar -->
                <div class="w-full bg-[#080d1a] border border-gray-800 rounded-full h-2 mt-3 overflow-hidden">
                    <div class="bg-[#76c800] h-2 rounded-full transition-all duration-500 shadow-[0_0_10px_#76c800]" 
                         style="width: {{ min(100, (($member->reward_points ?? 0) / 500) * 100) }}%"></div>
                </div>

                <p class="text-[11px] text-gray-400 mt-2">
                    Earn <span class="text-white font-semibold">3 pts</span> on daily sessions & <span class="text-[#76c800] font-semibold">15 pts</span> on monthly passes.
                </p>
            </div>

            <!-- Redemption Action Button -->
            @if(($member->reward_points ?? 0) >= 500)
                <form action="{{ route('member.redeemPoints') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-xs uppercase font-heading rounded-lg shadow-lg transition tracking-wider flex items-center justify-center space-x-2">
                        <span>🎁</span><span>Redeem 500 PTS for 1 Month Free Access</span>
                    </button>
                </form>
            @else
                <div class="text-[10px] text-gray-500 font-mono flex items-center justify-between border-t border-[#1e293b] pt-2">
                    <span>Target: 500 PTS</span>
                    <span>Remaining: {{ max(0, 500 - ($member->reward_points ?? 0)) }} PTS</span>
                </div>
            @endif
        </div>

        <!-- Card 3: Expiration & Training Counter -->
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">PASS EXPIRATION</span>
                <div class="text-xl font-heading font-extrabold text-[#76c800] mt-2 font-mono">
                    {{ $member->latestSubscription ? $member->latestSubscription->end_time->format('Y-m-d H:i') : '2026-10-22 23:59' }}
                </div>
            </div>
            <div class="pt-4 border-t border-[#1e293b] flex justify-between items-center">
                <span class="text-xs text-gray-400">Total Workouts: <strong class="text-white font-mono text-sm">{{ $member->gymNotes->count() }}</strong></span>
                <a href="{{ route('member.notes.index') }}" class="text-xs text-[#76c800] hover:underline font-heading font-semibold">
                    View Logs →
                </a>
            </div>
        </div>

    </div>

    <!-- 2. FLUID TWO-COLUMN WORKSPACE (Utilizing Widescreen Display) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- LEFT COLUMN: ANNOUNCEMENTS & ATTENDANCE LOG (7 Columns Wide) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- Tabular: Live Gym Announcements Board -->
            @php
                $memberAnnouncements = \App\Models\Announcement::latest('posted_date')->take(6)->get();
            @endphp
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span>📢</span><span>GYM ANNOUNCEMENTS BOARD</span>
                    </h3>
                    <span class="text-xs font-mono text-gray-400">{{ $memberAnnouncements->count() }} Updates</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-3">DATE</th>
                                <th class="p-3">TAG</th>
                                <th class="p-3">ANNOUNCEMENT DETAILS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] text-gray-300">
                            @forelse($memberAnnouncements as $anc)
                            <tr class="hover:bg-[#131d33] transition">
                                <td class="p-3 font-mono text-gray-400 whitespace-nowrap">{{ $anc->posted_date->format('M d, Y') }}</td>
                                <td class="p-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold 
                                        @if($anc->badge === 'IMPORTANT') bg-red-950 text-red-400 border border-red-800/40
                                        @elseif($anc->badge === 'SCHEDULE') bg-blue-950 text-sky-400 border border-sky-800/40
                                        @elseif($anc->badge === 'PROMO') bg-emerald-950 text-[#76c800] border border-[#76c800]/40
                                        @else bg-gray-800 text-gray-300 @endif">
                                        {{ $anc->badge }}
                                    </span>
                                </td>
                                <td class="p-3">
                                    <strong class="text-white block text-sm">{{ $anc->title }}</strong>
                                    <p class="text-gray-300 text-xs mt-0.5 leading-relaxed">{{ $anc->message }}</p>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-4 text-center text-gray-500 font-sans italic">
                                    No announcements published right now. Check back soon for gym schedules and updates!
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Gym Visit Attendance History -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span>🗓️</span><span>GYM VISIT LOG SHEET</span>
                    </h3>
                    <span class="text-xs font-mono text-gray-400">Total Visits: {{ $member->attendances->count() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-3">VISIT DATE</th>
                                <th class="p-3">TIME IN</th>
                                <th class="p-3">PASS TYPE</th>
                                <th class="p-3 text-right">POINTS CREDITED</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] text-gray-300">
                            @forelse($member->attendances as $att)
                            <tr class="hover:bg-[#131d33] transition">
                                <td class="p-3 font-bold text-white">{{ \Carbon\Carbon::parse($att->attendance_date)->format('Y-m-d') }}</td>
                                <td class="p-3 text-gray-400">{{ $att->check_in_time }}</td>
                                <td class="p-3 font-sans">
                                    @if($att->entry_type === 'membership')
                                        <span class="text-[#76c800] font-semibold">Monthly Member Pass</span>
                                    @else
                                        <span class="text-yellow-400 font-semibold">Per-Session Entry (₱50)</span>
                                    @endif
                                </td>
                                <td class="p-3 text-right font-sans">
                                    <span class="text-[#76c800] font-bold font-mono">
                                        {{ $att->entry_type === 'membership' ? 'Active Pass' : '+3 PTS' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500 font-sans italic">No gym visits recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT COLUMN: PAYMENT ACTION, REQUESTS, & LEDGER (5 Columns Wide) -->
        <div class="lg:col-span-5 space-y-6">
            
            <!-- Quick Renewal Trigger Card -->
            <div class="bg-gradient-to-br from-[#0f172a] to-[#131d33] border border-[#1e293b] rounded-2xl p-6 shadow-md flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-heading font-extrabold text-white uppercase tracking-wider">Renew Subscription</h4>
                    <p class="text-xs text-gray-400 mt-0.5">Select monthly, quarterly, or yearly online.</p>
                </div>
                <button onclick="document.getElementById('paymentModal').showModal()" 
                    class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-5 py-2.5 rounded-xl text-xs uppercase font-heading shadow-lg shadow-[#76c800]/20 transition flex items-center space-x-1.5">
                    <span>💳</span><span>Make Payment</span>
                </button>
            </div>

            <!-- Tabular: Payment Requests -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-4">
                <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">PAYMENT REQUESTS</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-2.5">PLAN</th>
                                <th class="p-2.5">AMOUNT</th>
                                <th class="p-2.5">REF NO.</th>
                                <th class="p-2.5 text-right">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] font-mono text-gray-300">
                            @forelse($member->payments as $p)
                            <tr>
                                <td class="p-2.5 font-sans font-semibold text-white">
                                    {{ $p->payment_type === 'monthly_subscription' ? 'Monthly Pass' : 'Daily' }}
                                </td>
                                <td class="p-2.5 text-[#76c800] font-bold">₱{{ number_format($p->amount, 2) }}</td>
                                <td class="p-2.5 text-gray-300 text-[11px]">{{ $p->reference_number ?? '—' }}</td>
                                <td class="p-2.5 text-right font-sans">
                                    @if($p->status === 'verified')
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-950 text-[#76c800]">APPROVED</span>
                                    @elseif($p->status === 'rejected')
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-red-950 text-red-400">REJECTED</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-yellow-950 text-yellow-400">PENDING</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-3 text-center text-gray-500 font-sans italic">No payment requests submitted yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Payment Receipts History -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-2xl p-6 shadow-md space-y-4">
                <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">PAYMENT HISTORY</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs font-mono">
                        <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[10px]">
                            <tr>
                                <th class="p-2.5">CODE</th>
                                <th class="p-2.5">DATE</th>
                                <th class="p-2.5 text-right">AMOUNT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] text-gray-300">
                            @forelse($member->payments->where('status', 'verified') as $pay)
                            <tr>
                                <td class="p-2.5 text-gray-400">{{ $pay->payment_code }}</td>
                                <td class="p-2.5">{{ $pay->created_at->format('Y-m-d') }}</td>
                                <td class="p-2.5 text-right text-[#76c800] font-bold">₱{{ number_format($pay->amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-3 text-center text-gray-500 font-sans italic">No verified receipts yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL: MAKE A MEMBERSHIP PAYMENT (MONTHLY, QUARTERLY, YEARLY)             -->
<!-- ========================================================================= -->
<dialog id="paymentModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">Renew Membership Subscription</h3>
        <button onclick="document.getElementById('paymentModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    
    <form action="{{ route('member.paySubscription') }}" method="POST" class="space-y-4 text-xs">
        @csrf
        
        <div>
            <label class="block text-gray-400 mb-1 font-semibold">Select Membership Plan *</label>
            <select name="package_id" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded-lg p-2.5 text-white font-medium focus:border-[#76c800] outline-none">
                @foreach($packages->where('plan_type', '!=', 'daily') as $pkg)
                    <option value="{{ $pkg->id }}">
                        {{ $pkg->name }} — ₱{{ number_format($pkg->price) }}
                        ({{ $pkg->duration_in_days >= 365 ? '12 Months' : ($pkg->duration_in_days >= 90 ? '3 Months' : '1 Month') }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-gray-400 mb-1 font-semibold">Online Payment Channel *</label>
            <select name="payment_method_id" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded-lg p-2.5 text-white font-medium focus:border-[#76c800] outline-none">
                @foreach($paymentMethods->where('code', '!=', 'cash') as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }} (Online Transfer)</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-gray-400 mb-1 font-semibold">Online Reference Number *</label>
            <input type="text" name="reference_number" required placeholder="e.g. Maya-20260910-77341 / GCash Ref" 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded-lg p-2.5 text-white font-mono focus:border-[#76c800] outline-none">
            <p class="text-[10px] text-gray-500 mt-1">Earns 15 PTS (Monthly), 45 PTS (Quarterly), or 180 PTS (Yearly) once verified.</p>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('paymentModal').close()" class="px-3.5 py-2 border border-gray-800 text-gray-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold rounded-lg uppercase font-heading transition shadow-lg shadow-[#76c800]/20">
                Submit Request
            </button>
        </div>
    </form>
</dialog>
@endsection