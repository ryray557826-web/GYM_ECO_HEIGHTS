@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- 1. Top 4 Stat Cockpit Cards -->
    @include('owner._top_stats')

    <!-- 2. Owner Navigation Tabs -->
    @include('owner._navigation')

    <!-- 3. Check-In Main Grid (12 Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Check-in Box & 6-Month Chart -->
        <div class="lg:col-span-6 space-y-6">
            
            <!-- Quick Member Check-In Box -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-sm font-heading font-bold uppercase tracking-wider text-white">PER-SESSION CHECK-IN</h3>
                <p class="text-xs text-gray-400 mt-1 mb-4">Enter a Member ID to look up the member and record an entry.</p>

                <div class="flex gap-2">
                    <input type="text" id="member_search_id" placeholder="ECO-001" 
                        class="flex-1 bg-[#080d1a] border border-[#1e293b] rounded-lg px-4 py-2.5 text-xs uppercase font-mono text-white focus:outline-none focus:border-[#76c800]"
                        onkeydown="if(event.key === 'Enter') searchMember()">
                    <button type="button" onclick="searchMember()" 
                        class="bg-[#1e293b] hover:bg-[#334155] border border-gray-700 text-white font-semibold px-5 py-2.5 rounded-lg text-xs transition font-heading">
                        Look Up
                    </button>
                </div>
            </div>

            <!-- 6-Month Revenue vs Expenses Chart (Directly on Front Page) -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white mb-4">
                    6-MONTH REVENUE VS EXPENSES
                </h3>
                <div class="relative h-64">
                    <canvas id="revenueExpensesChartFront"></canvas>
                </div>
            </div>

        </div>

        <!-- Right Column: Today's Entries (Table), Recent Entries (Table) & Announcements (Table) -->
        <div class="lg:col-span-6 space-y-6">
            
            <!-- Tabular: Today's Entries -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow space-y-3">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">TODAY'S ENTRIES</h3>
                <p class="text-[11px] text-gray-400 font-mono">{{ \Carbon\Carbon::now()->format('l, F j, Y') }}</p>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-gray-400 uppercase tracking-wider border-b border-[#1e293b] text-[10px]">
                            <tr>
                                <th class="pb-2">TIME</th>
                                <th class="pb-2">MEMBER</th>
                                <th class="pb-2">ENTRY TYPE</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                            @forelse($todayEntries ?? [] as $entry)
                            <tr>
                                <td class="py-2">{{ $entry->check_in_time }}</td>
                                <td class="py-2 font-sans font-semibold text-white">
                                    {{ $entry->customer ? $entry->customer->full_name : 'Customer' }}
                                    <span class="text-gray-400 text-[11px]">({{ $entry->member ? $entry->member->member_code : 'Walk-In' }})</span>
                                </td>
                                <td class="py-2 font-sans">
                                    @if($entry->entry_type === 'membership')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-[#76c800]">Active Monthly Pass</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-yellow-950 text-yellow-400">₱50 Paid Entry</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="py-3 text-center text-gray-500 font-sans italic">No check-ins recorded today yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Recent Per-Session Entries -->
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow space-y-3">
                <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white">
                    RECENT PER-SESSION ENTRIES
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-gray-400 uppercase tracking-wider border-b border-[#1e293b] text-[10px]">
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
                                <td class="py-2 font-sans font-semibold text-white">{{ $r->customer ? $r->customer->full_name : 'Visitor' }}</td>
                                <td class="py-2">{{ $r->attendance_date ? \Carbon\Carbon::parse($r->attendance_date)->format('Y-m-d') : '2026-09-22' }}</td>
                                <td class="py-2">{{ $r->check_in_time }}</td>
                                <td class="py-2 text-right text-[#76c800] font-bold">₱50</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-3 text-center text-gray-500 font-sans italic">No per-session entries logged yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabular: Live Gym Announcements Manager for Owner -->
            @php
                $ownerAnnouncements = \App\Models\Announcement::latest('posted_date')->get();
            @endphp
            <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl p-6 shadow space-y-3">
                <div class="flex justify-between items-center">
                    <h3 class="text-xs font-heading font-extrabold uppercase tracking-wider text-white flex items-center space-x-2">
                        <span>📢</span><span>GYM ANNOUNCEMENTS MANAGER</span>
                    </h3>
                    <button onclick="document.getElementById('addAnnouncementModal').showModal()" 
                        class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-3 py-1 rounded text-xs uppercase font-heading transition">
                        + Post Announcement
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-gray-400 uppercase tracking-wider border-b border-[#1e293b] text-[10px]">
                            <tr>
                                <th class="pb-2">DATE</th>
                                <th class="pb-2">TAG</th>
                                <th class="pb-2">TITLE & MESSAGE</th>
                                <th class="pb-2 text-right">ACTION</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#1e293b] text-gray-300">
                            @forelse($ownerAnnouncements as $oa)
                            <tr>
                                <td class="py-2 font-mono text-gray-400 text-[11px] whitespace-nowrap">{{ $oa->posted_date->format('Y-m-d') }}</td>
                                <td class="py-2 whitespace-nowrap">
                                    <span class="px-2 py-0.2 rounded text-[9px] font-bold 
                                        @if($oa->badge === 'IMPORTANT') bg-red-950 text-red-400
                                        @elseif($oa->badge === 'SCHEDULE') bg-blue-950 text-sky-400
                                        @elseif($oa->badge === 'PROMO') bg-emerald-950 text-[#76c800]
                                        @else bg-gray-800 text-gray-300 @endif">
                                        {{ $oa->badge }}
                                    </span>
                                </td>
                                <td class="py-2">
                                    <strong class="text-white block">{{ $oa->title }}</strong>
                                    <span class="text-gray-400 text-[11px]">{{ $oa->message }}</span>
                                </td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    <form action="{{ route('owner.announcements.destroy', $oa->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300 text-xs ml-2" title="Delete Announcement">✕</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-3 text-center text-gray-500 italic">No announcements posted yet. Click "+ Post Announcement" to publish one.</td>
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
<!-- MODAL: MEMBER FOUND (STRICT SINGLE-BUTTON CHECK-IN LOGIC)                 -->
<!-- ========================================================================= -->
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
        <div class="flex justify-between text-gray-400">
            <span>Membership Plan:</span>
            <span id="modal_m_plan" class="text-white font-semibold"></span>
        </div>
        <div class="flex justify-between text-gray-400">
            <span>Pass Expiration:</span>
            <span id="modal_m_expires" class="font-mono text-white"></span>
        </div>
        <div class="flex justify-between text-gray-400">
            <span>Reward Points Balance:</span>
            <span id="modal_m_points" class="font-bold text-[#76c800] font-mono"></span>
        </div>
        <div class="flex justify-between text-gray-400">
            <span>Contact Number:</span>
            <span id="modal_m_contact" class="text-white font-mono"></span>
        </div>
    </div>

    <!-- Actions Area: STRICTLY ONLY ONE BUTTON SHOWN AT A TIME -->
    <div class="border-t border-[#1e293b] pt-4 space-y-2">
        <!-- 1. Shown ONLY if member has an active monthly or multi-month pass -->
        <button id="modal_btn_monthly" onclick="processMonthlyCheckin()" 
            class="hidden w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-xs uppercase font-heading transition shadow-lg shadow-blue-600/20">
            Check-In As Active Monthly Member (₱0)
        </button>

        <!-- 2. Shown ONLY if member is expired or paying a daily walk-in entry -->
        <button id="modal_btn_session" onclick="processSessionCheckin()" 
            class="hidden w-full bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold py-2.5 rounded-lg text-xs uppercase font-heading transition shadow-lg shadow-[#76c800]/20">
            Confirm Paid ₱50 & Log Entry
        </button>

        <button type="button" onclick="document.getElementById('memberFoundModal').close()" 
            class="w-full py-1.5 text-xs text-gray-400 hover:text-white transition">
            Close
        </button>
    </div>
</dialog>

<!-- ========================================================================= -->
<!-- MODAL: MEMBER NOT FOUND                                                   -->
<!-- ========================================================================= -->
<dialog id="memberNotFoundModal" class="bg-[#0f172a] border border-red-900/60 text-white p-6 rounded-2xl max-w-sm w-full shadow-2xl backdrop:bg-black/80">
    <div class="text-center space-y-3">
        <div class="w-12 h-12 rounded-full bg-red-950/70 border border-red-700/60 flex items-center justify-center mx-auto text-xl">
            ⚠️
        </div>
        <h4 class="text-base font-heading font-extrabold uppercase tracking-wider text-red-400">MEMBER NOT FOUND</h4>
        <p id="not_found_message" class="text-xs text-gray-300"></p>
        <button type="button" onclick="document.getElementById('memberNotFoundModal').close()" 
            class="w-full bg-[#1e293b] hover:bg-[#334155] border border-gray-700 text-white font-bold py-2 rounded text-xs font-heading uppercase transition">
            Dismiss
        </button>
    </div>
</dialog>

<!-- ========================================================================= -->
<!-- MODAL: POST LIVE ANNOUNCEMENT                                            -->
<!-- ========================================================================= -->
<dialog id="addAnnouncementModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">Post New Gym Announcement</h3>
        <button onclick="document.getElementById('addAnnouncementModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.announcements.store') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div>
            <label class="block text-gray-400 mb-1">Announcement Title *</label>
            <input type="text" name="title" placeholder="e.g. Schedule Update or Holiday Hours" required 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Badge Tag</label>
            <select name="badge" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-semibold">
                <option value="INFO">INFO</option>
                <option value="IMPORTANT">IMPORTANT</option>
                <option value="SCHEDULE">SCHEDULE</option>
                <option value="PROMO">PROMO</option>
            </select>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Announcement Message *</label>
            <textarea name="message" rows="3" placeholder="Type announcement here. It will immediately show on all member dashboards..." required 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white"></textarea>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('addAnnouncementModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold rounded uppercase font-heading transition">
                Publish Live
            </button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
// 1. Render 6-Month Bar Chart
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

// 2. Member ID Lookup with Strict Single-Button Logic
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

            // Populate Modal Information
            document.getElementById('modal_m_name').innerText = activeLookupMember.name;
            document.getElementById('modal_m_id').innerText = activeLookupMember.member_id;
            document.getElementById('modal_m_plan').innerText = activeLookupMember.plan_type;
            document.getElementById('modal_m_expires').innerText = activeLookupMember.expires_at;
            document.getElementById('modal_m_points').innerText = `${activeLookupMember.reward_points} PTS / 500 PTS`;
            document.getElementById('modal_m_contact').innerText = activeLookupMember.contact;

            const badge = document.getElementById('modal_m_badge');
            const btnMonthly = document.getElementById('modal_btn_monthly');
            const btnSession = document.getElementById('modal_btn_session');

            // =========================================================================
            // STRICT EXCLUSIVE BUTTON CONDITION:
            // - Active Pass:        SHOW ONLY BLUE (₱0), HIDE GREEN
            // - Expired / No Pass:  SHOW ONLY GREEN (₱50), HIDE BLUE
            // =========================================================================
            if (activeLookupMember.has_active_monthly) {
                badge.innerText = "ACTIVE PASS";
                badge.className = "px-2.5 py-1 text-xs font-bold rounded bg-emerald-950 text-[#76c800] border border-[#76c800]/40";
                btnMonthly.classList.remove('hidden'); // Show ONLY monthly pass check-in
                btnSession.classList.add('hidden');    // Hide ₱50 per-session
            } else {
                badge.innerText = activeLookupMember.status || 'EXPIRED';
                badge.className = "px-2.5 py-1 text-xs font-bold rounded bg-red-950 text-red-400 border border-red-800/40";
                btnSession.classList.remove('hidden'); // Show ONLY ₱50 per-session
                btnMonthly.classList.add('hidden');    // Hide monthly pass check-in
            }

            document.getElementById('memberFoundModal').showModal();
        } else {
            document.getElementById('not_found_message').innerText = res.message;
            document.getElementById('memberNotFoundModal').showModal();
        }
    })
    .catch(() => {
        document.getElementById('not_found_message').innerText = "An error occurred while connecting to the database.";
        document.getElementById('memberNotFoundModal').showModal();
    });
}

// 3. Confirm ₱50 Paid Entry (Earns 3 points)
function processSessionCheckin() {
    if(!activeLookupMember) return;
    fetch("{{ route('owner.quickCheckIn.perSession') }}", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": "{{ csrf_token() }}" },
        body: JSON.stringify({ member_id: activeLookupMember.id })
    }).then(() => location.reload());
}

// 4. Confirm Monthly Free Entry (₱0)
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