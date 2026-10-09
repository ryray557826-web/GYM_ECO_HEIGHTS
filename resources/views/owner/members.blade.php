@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <!-- Actions Toolbar -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
            <input type="text" id="member_table_search" placeholder="Search by name or Member ID..." onkeyup="filterMemberTable()"
                class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500">
            
            <div class="flex items-center space-x-2">
                <button onclick="document.getElementById('addMemberModal').showModal()" 
                    class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase font-heading transition shadow-lg shadow-emerald-500/20">
                    + Add Approved Member
                </button>
                <a href="{{ route('owner.payments.index') }}" 
                    class="bg-slate-800 hover:bg-slate-700 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs uppercase font-heading">
                    + Record Payment
                </a>
            </div>
        </div>

        <!-- Members Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-x-auto shadow-md">
            <table class="w-full text-left text-xs" id="members_table">
                <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                    <tr>
                        <th class="p-3.5">ID</th>
                        <th class="p-3.5">NAME</th>
                        <th class="p-3.5">TYPE</th>
                        <th class="p-3.5">STATUS</th>
                        <th class="p-3.5">REWARD PTS</th>
                        <th class="p-3.5">EXPIRES</th>
                        <th class="p-3.5">CONTACT</th>
                        <th class="p-3.5 text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300 font-mono">
                    @forelse($members as $m)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="p-3.5 text-emerald-400 font-bold">{{ $m->member_code }}</td>
                        <td class="p-3.5 font-sans font-bold text-white">{{ $m->customer ? $m->customer->full_name : 'No profile' }}</td>
                        <td class="p-3.5 font-sans text-slate-400">
                            {{ $m->latestSubscription && $m->latestSubscription->package ? $m->latestSubscription->package->name : 'Walk-In' }}
                        </td>
                        <td class="p-3.5 font-sans">
                            @if($m->membership_status === 'active')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-emerald-950 text-emerald-400 border border-emerald-500/30">ACTIVE</span>
                            @elseif($m->membership_status === 'expired')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-rose-950 text-rose-400 border border-rose-800/30">EXPIRED</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-amber-950 text-amber-400 border border-amber-800/30">PENDING APPROVAL</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-emerald-400 font-bold">{{ $m->reward_points ?? 0 }} PTS</td>
                        <td class="p-3.5">{{ $m->latestSubscription ? $m->latestSubscription->end_time->format('Y-m-d') : 'N/A' }}</td>
                        <td class="p-3.5">{{ $m->customer ? $m->customer->contact_number : '—' }}</td>
                        <td class="p-3.5 text-right font-sans space-x-2">
                            @if($m->membership_status === 'pending')
                                <form action="{{ route('owner.members.approve', $m->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs px-2.5 py-1 rounded">
                                        Approve
                                    </button>
                                </form>
                            @endif
                            <button onclick="openEditMemberModal({{ json_encode($m) }}, {{ json_encode($m->customer) }}, '{{ $m->latestSubscription ? $m->latestSubscription->end_time->format('Y-m-d') : '' }}')" 
                                class="text-xs text-slate-400 hover:text-white underline">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-slate-500 font-sans italic">No members found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Approved Member (Directly Active) -->
<dialog id="addMemberModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <div>
            <span class="text-[10px] uppercase tracking-widest text-emerald-400 font-heading font-bold">DIRECT ENROLLMENT</span>
            <h3 class="text-sm font-bold text-white">Add Pre-Approved Gym Member</h3>
        </div>
        <button onclick="document.getElementById('addMemberModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.members.store') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">First Name *</label>
                <input type="text" name="first_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Last Name *</label>
                <input type="text" name="last_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Email Address *</label>
            <input type="email" name="email" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">Contact Phone *</label>
                <input type="text" name="contact_number" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Initial Plan (Optional)</label>
                <select name="package_id" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
                    <option value="">No Initial Pass</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}">{{ $pkg->name }} (₱{{ number_format($pkg->price) }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Home Address</label>
            <input type="text" name="address" placeholder="Toril, Davao City" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
        </div>
        <div class="bg-emerald-950/40 border border-emerald-500/20 p-2.5 rounded-lg text-[11px] text-emerald-300 flex items-center space-x-2">
            <span>✓</span>
            <span>This member will be registered with <strong>Active / Approved</strong> status immediately.</span>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('addMemberModal').close()" class="px-3 py-1.5 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-lg uppercase font-heading">Enroll & Approve</button>
        </div>
    </form>
</dialog>

<!-- Modal: Edit Member -->
<dialog id="editMemberModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <div>
            <span class="text-[10px] uppercase tracking-widest text-emerald-400 font-heading font-bold">EDIT PROFILE</span>
            <h3 id="edit_member_title" class="text-sm font-bold text-white"></h3>
        </div>
        <button onclick="document.getElementById('editMemberModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <form id="editMemberForm" method="POST" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">First Name</label>
                <input type="text" name="first_name" id="edit_first_name" required class="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Last Name</label>
                <input type="text" name="last_name" id="edit_last_name" required class="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Contact Phone</label>
            <input type="text" name="contact_number" id="edit_contact" class="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white font-mono">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">Membership Status</label>
                <select name="membership_status" id="edit_status" class="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white font-semibold">
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="expired">Expired</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Plan Expiry Date</label>
                <input type="date" name="end_date" id="edit_end_date" class="w-full bg-slate-950 border border-slate-800 rounded p-2 text-white font-mono">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('editMemberModal').close()" class="px-3 py-1.5 border border-slate-800 text-slate-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-emerald-500 text-slate-950 font-extrabold rounded uppercase font-heading">Update</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
function filterMemberTable() {
    const input = document.getElementById('member_table_search').value.toLowerCase();
    document.querySelectorAll('#members_table tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(input) ? '' : 'none';
    });
}

function openEditMemberModal(member, customer, endDate) {
    document.getElementById('edit_member_title').innerText = `${customer ? customer.first_name + ' ' + customer.last_name : 'Member'} (${member.member_code})`;
    document.getElementById('edit_first_name').value = customer ? customer.first_name : '';
    document.getElementById('edit_last_name').value = customer ? customer.last_name : '';
    document.getElementById('edit_contact').value = customer ? customer.contact_number : '';
    document.getElementById('edit_status').value = member.membership_status || 'active';
    document.getElementById('edit_end_date').value = endDate || '';
    document.getElementById('editMemberForm').action = `/owner/members/${member.id}/update`;
    document.getElementById('editMemberModal').showModal();
}
</script>
@endpush
@endsection