@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-6">
        <!-- Financial Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
                <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">TOTAL REVENUE</span>
                <div class="text-3xl font-heading font-extrabold text-[#76c800] mt-1">₱{{ number_format($totalRevenue) }}</div>
            </div>
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
                <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">TOTAL EXPENSES</span>
                <div class="text-3xl font-heading font-extrabold text-[#e14b4b] mt-1">₱{{ number_format($totalExpenses) }}</div>
            </div>
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-5 shadow">
                <span class="text-xs uppercase font-heading font-semibold text-gray-400 tracking-wider">NET INCOME</span>
                <div class="text-3xl font-heading font-extrabold {{ $netIncome >= 0 ? 'text-[#76c800]' : 'text-[#e14b4b]' }} mt-1">
                    ₱{{ number_format($netIncome) }}
                </div>
            </div>
        </div>

        <!-- 6-Month Chart -->
        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
            <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white mb-4">
                6-MONTH REVENUE VS EXPENSES
            </h3>
            <div class="relative h-64">
                <canvas id="financeRevenueChart"></canvas>
            </div>
        </div>

        <!-- Expenses Ledger -->
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">EXPENSE RECORDS</h3>
                <button onclick="document.getElementById('addExpenseModal').showModal()" 
                    class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-3 py-1.5 rounded text-xs uppercase font-heading">
                    + Add Expense
                </button>
            </div>

            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
                <table class="w-full text-left text-xs">
                    <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                        <tr>
                            <th class="p-3">ID</th>
                            <th class="p-3">CATEGORY</th>
                            <th class="p-3">DESCRIPTION</th>
                            <th class="p-3">AMOUNT</th>
                            <th class="p-3">DATE</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                        @forelse($expenses as $exp)
                        <tr>
                            <td class="p-3 text-gray-400">{{ $exp->expense_code }}</td>
                            <td class="p-3 font-sans">{{ $exp->category }}</td>
                            <td class="p-3 font-sans font-bold text-white">{{ $exp->description }}</td>
                            <td class="p-3 text-[#e14b4b] font-bold">₱{{ number_format($exp->amount, 2) }}</td>
                            <td class="p-3">{{ $exp->expense_date }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-4 text-center text-gray-500 font-sans">No expenses logged yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<dialog id="addExpenseModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">+ Record Gym Expense</h3>
        <button onclick="document.getElementById('addExpenseModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.expenses.store') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div>
            <label class="block text-gray-400 mb-1">Category</label>
            <select name="category" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                <option value="Equipment">Equipment Purchase</option>
                <option value="Maintenance">Maintenance Costs</option>
                <option value="Damages">Damages & Repairs</option>
                <option value="Utilities">Utilities & Rent</option>
                <option value="Supplies">Cleaning & Supplies</option>
            </select>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Description *</label>
            <input type="text" name="description" placeholder="e.g. Treadmill belt replacement" required 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Amount (₱) *</label>
                <input type="number" step="0.01" name="amount" placeholder="0.00" required 
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Date</label>
                <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required 
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('addExpenseModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Save Expense</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById('financeRevenueChart');
    if (el) {
        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [
                    { label: 'Revenue', data: [8000, 10000, 11500, 10500, 14000, {{ $totalRevenue }}], backgroundColor: '#76c800', borderRadius: 4 },
                    { label: 'Expenses', data: [1200, 1500, 800, 3500, 5000, {{ $totalExpenses }}], backgroundColor: '#dc2626', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#6b7280' } },
                    y: { grid: { color: '#1e293b' }, ticks: { color: '#6b7280', callback: v => '₱' + (v / 1000) + 'k' } }
                }
            }
        });
    }
});
</script>
@endpush
@endsection