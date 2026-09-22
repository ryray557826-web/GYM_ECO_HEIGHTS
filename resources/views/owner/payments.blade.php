@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">
                PENDING APPROVALS <span class="bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded text-[11px] ml-1">{{ $pendingPayments->count() }}</span>
            </h3>
            <button onclick="document.getElementById('manualPaymentModal').showModal()" 
                class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-xs px-3.5 py-1.5 rounded uppercase font-heading">
                + Manually Add Payment
            </button>
        </div>

        <div class="space-y-3">
            @forelse($pendingPayments as $pay)
            <div class="bg-[#0f172a] border border-[#1e293b] p-4 rounded-xl flex justify-between items-center shadow">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="font-bold text-white text-sm">{{ $pay->customer ? $pay->customer->full_name : 'Customer' }}</span>
                        <span class="text-[#76c800] font-mono text-xs font-bold">{{ $pay->member ? $pay->member->member_code : 'Walk-In' }}</span>
                        <span class="bg-yellow-950 text-yellow-400 text-[10px] font-bold px-1.5 py-0.5 rounded">PENDING</span>
                    </div>
                    <p class="text-xs text-gray-400">
                        {{ $pay->payment_type === 'monthly_subscription' ? 'Monthly' : 'Daily' }} — 
                        <span class="text-[#76c800] font-bold">₱{{ number_format($pay->amount, 2) }}</span> · 
                        {{ $pay->method ? $pay->method->name : 'Manual' }}
                        @if($pay->reference_number)
                            · Ref: <span class="font-mono text-white">{{ $pay->reference_number }}</span>
                        @endif
                    </p>
                    <p class="text-[11px] text-gray-500 font-mono">Submitted {{ $pay->created_at->format('Y-m-d H:i') }}</p>
                </div>
                <div class="flex space-x-2">
                    <form action="{{ route('owner.payments.verify', $pay->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold text-xs px-4 py-1.5 rounded transition">✓ Approve</button>
                    </form>
                    <form action="{{ route('owner.payments.reject', $pay->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-[#1e1b2e] hover:bg-red-950 text-red-400 border border-red-900/40 text-xs px-4 py-1.5 rounded transition">✕ Reject</button>
                    </form>
                </div>
            </div>
            @empty
            <p class="text-gray-500 text-xs italic">No payments currently pending approval.</p>
            @endforelse
        </div>

        <div class="space-y-3 pt-3">
            <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">ALL REQUESTS / REVENUE LEDGER</h3>
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">MEMBER</th>
                            <th class="p-3">PLAN</th>
                            <th class="p-3">AMOUNT</th>
                            <th class="p-3">METHOD</th>
                            <th class="p-3">REFERENCE</th>
                            <th class="p-3">DATE</th>
                            <th class="p-3">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                        @forelse($payments as $p)
                        <tr>
                            <td class="p-3 text-gray-400">{{ $p->payment_code }}</td>
                            <td class="p-3 font-sans font-bold text-white">{{ $p->customer ? $p->customer->full_name : 'Customer' }}</td>
                            <td class="p-3 font-sans">{{ $p->payment_type === 'monthly_subscription' ? 'Monthly' : 'Daily' }}</td>
                            <td class="p-3 text-[#76c800] font-bold">₱{{ number_format($p->amount, 2) }}</td>
                            <td class="p-3 font-sans">{{ $p->method ? $p->method->name : 'Manual' }}</td>
                            <td class="p-3">{{ $p->reference_number ?? '—' }}</td>
                            <td class="p-3">{{ $p->created_at->format('Y-m-d H:i') }}</td>
                            <td class="p-3 font-sans">
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
                            <td colspan="8" class="p-4 text-center text-gray-500 font-sans">No payment records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Record Manual Payment -->
<dialog id="manualPaymentModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">Record Member Payment</h3>
        <button onclick="document.getElementById('manualPaymentModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.payments.manual') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div>
            <label class="block text-gray-400 mb-1">Select Member *</label>
            <select name="member_id" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->member_code }} — {{ $m->customer->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Payment Plan</label>
                <select name="plan_type" onchange="document.getElementById('manual_amount').value = (this.value === 'monthly' ? '750.00' : '50.00')" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                    <option value="monthly">Monthly Pass (₱750)</option>
                    <option value="per_session">Per-Session (₱50)</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Amount (₱)</label>
                <input type="number" step="0.01" name="amount" id="manual_amount" value="750.00" required 
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Payment Method</label>
            <select name="payment_method_id" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Reference Number</label>
            <input type="text" name="reference_number" placeholder="e.g. GCash-20260922-1002" 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('manualPaymentModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Confirm & Record</button>
        </div>
    </form>
</dialog>
@endsection