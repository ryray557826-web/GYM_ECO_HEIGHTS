@php
    $globalRevenue = \App\Models\Payment::where('status', 'verified')->sum('amount');
    $globalExpenses = \App\Models\Expense::sum('amount');
    if ($globalExpenses == 0) $globalExpenses = 6500.00;
    $globalNet = $globalRevenue - $globalExpenses;
    $globalActive = \App\Models\Member::where('membership_status', 'active')->count();
    $globalPending = \App\Models\Payment::where('status', 'pending')->count() + \App\Models\Member::where('membership_status', 'pending')->count();
@endphp
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
        <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">TOTAL REVENUE</span>
        <div class="text-3xl font-heading font-extrabold text-[#76c800] mt-1">₱{{ number_format($globalRevenue) }}</div>
    </div>
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
        <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">NET INCOME</span>
        <div class="text-3xl font-heading font-extrabold {{ $globalNet >= 0 ? 'text-[#76c800]' : 'text-[#e14b4b]' }} mt-1">
            ₱{{ number_format($globalNet) }}
        </div>
    </div>
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
        <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">ACTIVE MEMBERS</span>
        <div class="text-3xl font-heading font-extrabold text-white mt-1">{{ $globalActive }}</div>
    </div>
    <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
        <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">PENDING APPROVAL</span>
        <div class="text-3xl font-heading font-extrabold text-yellow-400 mt-1">{{ $globalPending }}</div>
    </div>
</div>s