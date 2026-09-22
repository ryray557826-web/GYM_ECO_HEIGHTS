@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Member Navigation Tabs -->
    <div class="border-b border-[#1e293b]">
        <nav class="flex space-x-8 text-xs font-heading font-bold uppercase tracking-wider">
            <a href="{{ route('member.overview') }}" class="py-3 text-[#76c800] border-b-2 border-[#76c800]">
                Overview
            </a>
            <a href="{{ route('member.notes.index') }}" class="py-3 text-gray-400 hover:text-gray-200 border-b-2 border-transparent flex items-center space-x-1.5">
                <span>Gym Notes</span>
                <span class="bg-[#1e293b] text-white text-[10px] px-1.5 py-0.2 rounded font-mono">{{ $member->gymNotes->count() }}</span>
            </a>
        </nav>
    </div>

    <!-- 3 Stat Cards in a Row -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
            <span class="text-[11px] uppercase font-heading font-semibold text-gray-400 tracking-wider">STATUS</span>
            <div class="mt-2 flex items-center space-x-2">
                <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-emerald-950 text-[#76c800]">
                    {{ strtoupper($member->membership_status ?? 'ACTIVE') }}
                </span>
            </div>
            <p class="text-xs text-gray-400 mt-3">Type: <span class="font-semibold text-white">Monthly</span></p>
        </div>

        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
            <span class="text-[11px] uppercase font-heading font-semibold text-gray-400 tracking-wider">EXPIRATION</span>
            <div class="text-lg font-heading font-extrabold text-[#76c800] mt-1 font-mono">
                {{ $member->latestSubscription ? $member->latestSubscription->end_time->format('Y-m-d H:i') : '2026-10-22 05:47' }}
            </div>
            <p class="text-[11px] text-gray-500 mt-2">Registered: {{ $member->joined_date ? $member->joined_date->format('Y-m-d') : '2026-08-18' }}</p>
        </div>

        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
            <span class="text-[11px] uppercase font-heading font-semibold text-gray-400 tracking-wider">WORKOUTS LOGGED</span>
            <div class="text-3xl font-heading font-extrabold text-white mt-1">{{ $member->gymNotes->count() }}</div>
            <a href="{{ route('member.notes.index') }}" class="text-xs text-[#76c800] hover:underline mt-2 block">
                View all →
            </a>
        </div>
    </div>

    <!-- Make a Payment Trigger Button -->
    <div>
        <button onclick="document.getElementById('paymentModal').showModal()" 
            class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-5 py-2.5 rounded-lg text-xs uppercase font-heading flex items-center space-x-2 shadow-lg shadow-[#76c800]/10">
            <span>💳</span><span>Make a Payment</span>
        </button>
    </div>

    <!-- Payment Requests Section -->
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow space-y-4">
        <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">PAYMENT REQUESTS</h3>
        @forelse($member->payments as $p)
        <div class="border border-[#76c800]/30 bg-emerald-950/20 p-4 rounded-xl flex items-start space-x-3">
            <span class="bg-[#76c800] text-black text-xs font-bold w-5 h-5 rounded flex items-center justify-center mt-0.5">✓</span>
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-white text-xs">{{ $p->payment_type === 'monthly_subscription' ? 'Monthly — ₱750' : 'Daily — ₱50' }}</span>
                    <span class="px-2 py-0.2 rounded font-bold text-[10px] bg-emerald-950 text-[#76c800]">{{ strtoupper($p->status) }}</span>
                </div>
                <p class="text-xs text-gray-400">{{ $p->method ? $p->method->name : 'Payment' }} · Ref: <span class="font-mono text-white">{{ $p->reference_number ?? 'N/A' }}</span></p>
                <p class="text-[11px] text-gray-500 font-mono">Submitted {{ $p->created_at->format('Y-m-d H:i') }}</p>
            </div>
        </div>
        @empty
        <p class="text-gray-500 text-xs italic">No payment requests submitted yet.</p>
        @endforelse
    </div>

    <!-- Gym Visit History Section (Green-Dot Timeline) -->
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow space-y-4">
        <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">GYM VISIT HISTORY</h3>
        <div class="space-y-3 font-mono text-xs">
            @forelse($member->attendances as $att)
            <div class="p-3 bg-[#080d1a] border border-[#1e293b] rounded-lg flex justify-between items-center">
                <div class="flex items-center space-x-2.5">
                    <span class="w-2 h-2 rounded-full bg-[#76c800]"></span>
                    <div>
                        <span class="font-bold text-white">{{ $att->attendance_date }}</span>
                        <div class="text-[11px] font-sans text-gray-400">{{ $att->entry_type === 'membership' ? 'Monthly member entry' : 'Per-session entry' }}</div>
                    </div>
                </div>
                <span class="text-gray-500">{{ $att->check_in_time }}</span>
            </div>
            @empty
            <p class="text-gray-500 text-xs font-sans italic">No gym visits recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal: Make a Payment -->
<dialog id="paymentModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-xl max-w-md w-full backdrop:bg-black/80">
    <h3 class="text-sm font-heading font-bold text-white uppercase mb-3">Make a Membership Payment</h3>
    <form action="{{ route('member.paySubscription') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div>
            <label class="block text-gray-400 mb-1">Select Membership Plan</label>
            <select name="package_id" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                @foreach($packages as $pkg)
                    <option value="{{ $pkg->id }}">{{ $pkg->name }} (₱{{ number_format($pkg->price) }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Payment Method</label>
            <select name="payment_method_id" id="member_payment_method" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white" 
                onchange="document.getElementById('member_ref_group').classList.toggle('hidden', this.options[this.selectedIndex].text.indexOf('Cash') !== -1)">
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                @endforeach
            </select>
        </div>
        <div id="member_ref_group">
            <label class="block text-gray-400 mb-1">Online Reference Number *</label>
            <input type="text" name="reference_number" placeholder="e.g. Maya-20260910-77341" 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
        </div>
        <div class="flex justify-end gap-2 pt-2 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('paymentModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Submit Request</button>
        </div>
    </form>
</dialog>
@endsection