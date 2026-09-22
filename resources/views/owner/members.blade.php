@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <div class="flex justify-between items-center gap-3">
            <input type="text" id="member_table_search" placeholder="Search by name or ID..." onkeyup="filterMemberTable()"
                class="flex-1 bg-[#0b1120] border border-[#1e293b] rounded-lg px-4 py-2.5 text-xs text-white focus:outline-none focus:border-[#76c800]">
            <a href="{{ route('owner.payments.index') }}" 
                class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-4 py-2.5 rounded-lg text-xs uppercase font-heading">
                + Record Payment
            </a>
        </div>

        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
            <table class="w-full text-left text-xs" id="members_table">
                <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">NAME</th>
                        <th class="p-3">TYPE</th>
                        <th class="p-3">STATUS</th>
                        <th class="p-3">EXPIRES</th>
                        <th class="p-3">CONTACT</th>
                        <th class="p-3 text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                    @forelse($members as $m)
                    <tr>
                        <td class="p-3 text-[#76c800] font-bold">{{ $m->member_code }}</td>
                        <td class="p-3 font-sans font-bold text-white">{{ $m->customer->full_name }}</td>
                        <td class="p-3 font-sans text-gray-400">
                            {{ $m->latestSubscription && $m->latestSubscription->package ? $m->latestSubscription->package->name : 'Walk-In' }}
                        </td>
                        <td class="p-3 font-sans">
                            @if($m->membership_status === 'active')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-emerald-950 text-[#76c800]">ACTIVE</span>
                            @elseif($m->membership_status === 'expired')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-red-950 text-red-400">EXPIRED</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-yellow-950 text-yellow-400">{{ strtoupper($m->membership_status) }}</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $m->latestSubscription ? $m->latestSubscription->end_time->format('Y-m-d') : 'N/A' }}</td>
                        <td class="p-3">{{ $m->customer->contact_number ?? '—' }}</td>
                        <td class="p-3 text-right font-sans space-x-2">
                            <button onclick="openEditMemberModal({{ json_encode($m) }}, {{ json_encode($m->customer) }}, '{{ $m->latestSubscription ? $m->latestSubscription->end_time->format('Y-m-d') : '' }}')" 
                                class="text-xs text-gray-400 hover:text-white underline">
                                Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500 font-sans">No members loaded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Edit Member -->
<dialog id="editMemberModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <div>
            <span class="text-[10px] uppercase tracking-widest text-[#76c800] font-heading font-bold">EDIT PROFILE</span>
            <h3 id="edit_member_title" class="text-sm font-bold text-white"></h3>
        </div>
        <button onclick="document.getElementById('editMemberModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form id="editMemberForm" method="POST" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">First Name *</label>
                <input type="text" name="first_name" id="edit_first_name" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Last Name *</label>
                <input type="text" name="last_name" id="edit_last_name" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Contact Number</label>
                <input type="text" name="contact_number" id="edit_contact" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Emergency Phone</label>
                <input type="text" name="emergency_contact_phone" id="edit_emergency" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Address</label>
            <input type="text" name="address" id="edit_address" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Membership Status</label>
                <select name="membership_status" id="edit_status" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-semibold">
                    <option value="active">Active</option>
                    <option value="expired">Expired</option>
                    <option value="pending">Pending</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Plan End Date</label>
                <input type="date" name="end_date" id="edit_end_date" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('editMemberModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Update Details</button>
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
    document.getElementById('edit_member_title').innerText = `${customer.first_name} ${customer.last_name} (${member.member_code})`;
    document.getElementById('edit_first_name').value = customer.first_name;
    document.getElementById('edit_last_name').value = customer.last_name;
    document.getElementById('edit_contact').value = customer.contact_number || '';
    document.getElementById('edit_emergency').value = customer.emergency_contact_phone || '';
    document.getElementById('edit_address').value = customer.address || '';
    document.getElementById('edit_status').value = member.membership_status || 'active';
    document.getElementById('edit_end_date').value = endDate || '';
    document.getElementById('editMemberForm').action = `/owner/members/${member.id}/update`;
    document.getElementById('editMemberModal').showModal();
}
</script>
@endpush
@endsection