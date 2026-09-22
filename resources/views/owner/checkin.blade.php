@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Top 4 Stat Cockpit -->
    @include('owner._top_stats')

    <!-- Owner Page Navigation Tabs -->
    @include('owner._navigation')

    <!-- Page Content -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-6 space-y-6">
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-sm font-heading font-bold uppercase tracking-wider text-white">PER-SESSION CHECK-IN</h3>
                <p class="text-xs text-gray-400 mt-1 mb-4">Enter a Member ID to look up the member and record a ₱50 per-session payment.</p>

                <div class="flex gap-2">
                    <input type="text" id="member_search_id" placeholder="ECO-002" 
                        class="flex-1 bg-[#080d1a] border border-[#1e293b] rounded-lg px-4 py-2.5 text-xs uppercase font-mono text-white focus:outline-none focus:border-[#76c800]"
                        onkeydown="if(event.key === 'Enter') searchMember()">
                    <button type="button" onclick="searchMember()" 
                        class="bg-[#1e293b] hover:bg-[#334155] border border-gray-700 text-white font-semibold px-5 py-2.5 rounded-lg text-xs transition font-heading">
                        Look Up
                    </button>
                </div>
            </div>

            <!-- Front Page 6-Month Chart -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white mb-4">
                    6-MONTH REVENUE VS EXPENSES
                </h3>
                <div class="relative h-64">
                    <canvas id="revenueExpensesChartFront"></canvas>
                </div>
            </div>
        </div>

        <div class="lg:col-span-6 space-y-6">
            <!-- Today's Entries -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">TODAY'S ENTRIES</h3>
                <p class="text-xs text-gray-400 mt-1 mb-4">{{ \Carbon\Carbon::now()->format('l, F j') }}</p>
                <div class="space-y-2">
                    @forelse($todayEntries ?? [] as $entry)
                        <div class="py-2 border-b border-gray-800 flex justify-between text-xs font-mono">
                            <span class="text-white font-sans">{{ $entry->customer ? $entry->customer->full_name : 'Customer' }} ({{ $entry->entry_type }})</span>
                            <span class="text-gray-400">{{ $entry->check_in_time }}</span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-500 italic">No check-ins recorded today yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Per-Session Entries -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white mb-4">
                    RECENT PER-SESSION ENTRIES
                </h3>
                <table class="w-full text-left text-xs">
                    <thead class="text-gray-400 uppercase tracking-wider border-b border-[#1e293b]">
                        <tr>
                            <th class="pb-2">MEMBER</th>
                            <th class="pb-2">DATE</th>
                            <th class="pb-2">TIME</th>
                            <th class="pb-2 text-right">AMOUNT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1e293b] font-mono text-gray-300">
                        @forelse($recentPerSession ?? [] as $r)
                        <tr>
                            <td class="py-2.5 font-sans font-semibold text-white">{{ $r->customer ? $r->customer->full_name : 'Visitor' }}</td>
                            <td class="py-2.5">{{ $r->attendance_date }}</td>
                            <td class="py-2.5">{{ $r->check_in_time }}</td>
                            <td class="py-2.5 text-right text-[#76c800] font-bold">₱50</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-3 text-center text-gray-500 font-sans">No per-session entries logged yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Member Found Dialog -->
<dialog id="memberFoundModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-start pb-3 border-b border-[#1e293b]">
        <div>
            <span class="text-[10px] uppercase tracking-widest text-[#76c800] font-heading font-bold">MEMBER LOOKUP</span>
            <h3 id="modal_m_name" class="text-xl font-bold text-white mt-0.5"></h3>
            <p class="text-xs font-mono text-gray-400">ID: <span id="modal_m_id" class="text-[#76c800] font-bold"></span></p>
        </div>
        <span id="modal_m_badge" class="px-2.5 py-1 text-xs font-bold rounded"></span>
    </div>
    <div class="py-4 space-y-2 text-xs">
        <div class="flex justify-between text-gray-400"><span>Plan:</span><span id="modal_m_plan" class="text-white font-semibold"></span></div>
        <div class="flex justify-between text-gray-400"><span>Expires:</span><span id="modal_m_expires" class="font-mono text-white"></span></div>
        <div class="flex justify-between text-gray-400"><span>Contact:</span><span id="modal_m_contact" class="text-white"></span></div>
    </div>
    <div class="border-t border-[#1e293b] pt-4 space-y-2">
        <button onclick="processSessionCheckin()" class="w-full bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold py-2.5 rounded text-xs uppercase font-heading transition">
            Confirm Paid ₱50 & Log Entry
        </button>
        <button id="modal_btn_monthly" onclick="processMonthlyCheckin()" class="hidden w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded text-xs uppercase font-heading transition">
            Check-In As Active Monthly Member (₱0)
        </button>
        <button onclick="document.getElementById('memberFoundModal').close()" class="w-full py-1.5 text-xs text-gray-400 hover:text-white">Close</button>
    </div>
</dialog>

<!-- Modal: Member Not Found -->
<dialog id="memberNotFoundModal" class="bg-[#0f172a] border border-red-900/60 text-white p-6 rounded-2xl max-w-sm w-full shadow-2xl backdrop:bg-black/80">
    <div class="text-center space-y-3">
        <div class="w-12 h-12 rounded-full bg-red-950/70 border border-red-700/60 flex items-center justify-center mx-auto text-xl">⚠️</div>
        <h4 class="text-base font-heading font-extrabold uppercase tracking-wider text-red-400">MEMBER NOT FOUND</h4>
        <p id="not_found_message" class="text-xs text-gray-300"></p>
        <button onclick="document.getElementById('memberNotFoundModal').close()" class="w-full bg-[#1e293b] hover:bg-[#334155] border border-gray-700 text-white font-bold py-2 rounded text-xs font-heading uppercase">
            Dismiss
        </button>
    </div>
</dialog>

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const el = document.getElementById('revenueExpensesChartFront');
    if (el) {
        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
                datasets: [
                    { label: 'Revenue', data: [8000, 10000, 11500, 10500, 14000, {{ $totalRevenue ?? 4600 }}], backgroundColor: '#76c800', borderRadius: 4 },
                    { label: 'Expenses', data: [1200, 1500, 800, 3500, 5000, {{ $totalExpenses ?? 6500 }}], backgroundColor: '#dc2626', borderRadius: 4 }
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

let activeLookupMember = null;
function searchMember() {
    const id = document.getElementById('member_search_id').value.trim();
    if(!id) return;
    fetch("{{ route('owner.quickCheckIn.search') }}", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: JSON.stringify({ member_id: id })
    })
    .then(r => r.json())
    .then(res => {
        if(res.success) {
            activeLookupMember = res.member;
            document.getElementById('modal_m_name').innerText = activeLookupMember.name;
            document.getElementById('modal_m_id').innerText = activeLookupMember.member_id;
            document.getElementById('modal_m_plan').innerText = activeLookupMember.plan_type;
            document.getElementById('modal_m_expires').innerText = activeLookupMember.expires_at;
            document.getElementById('modal_m_contact').innerText = activeLookupMember.contact;

            const badge = document.getElementById('modal_m_badge');
            const btnMonthly = document.getElementById('modal_btn_monthly');
            if(activeLookupMember.has_active_monthly) {
                badge.innerText = "ACTIVE PASS";
                badge.className = "px-2.5 py-1 text-xs font-bold rounded bg-emerald-950 text-[#76c800]";
                btnMonthly.classList.remove('hidden');
            } else {
                badge.innerText = activeLookupMember.status;
                badge.className = "px-2.5 py-1 text-xs font-bold rounded bg-gray-800 text-gray-300";
                btnMonthly.classList.add('hidden');
            }
            document.getElementById('memberFoundModal').showModal();
        } else {
            document.getElementById('not_found_message').innerText = res.message;
            document.getElementById('memberNotFoundModal').showModal();
        }
    });
}

function processSessionCheckin() {
    if(!activeLookupMember) return;
    fetch("{{ route('owner.quickCheckIn.perSession') }}", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: JSON.stringify({ member_id: activeLookupMember.id })
    }).then(() => location.reload());
}

function processMonthlyCheckin() {
    if(!activeLookupMember) return;
    fetch("{{ route('owner.quickCheckIn.monthly') }}", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: JSON.stringify({ member_id: activeLookupMember.id })
    }).then(() => location.reload());
}
</script>
@endpush
@endsection