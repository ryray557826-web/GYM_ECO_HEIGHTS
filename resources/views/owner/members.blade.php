@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <!-- Flash Notifications -->
    @if(session('success'))
        <div class="bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 px-4 py-3 rounded-xl text-xs font-semibold flex items-center justify-between">
            <span>✓ {{ session('success') }}</span>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
        </div>
    @endif

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
                        <th class="p-3.5">VERIFICATION</th>
                        <th class="p-3.5">PASS ACCESS</th>
                        <th class="p-3.5">REWARD PTS</th>
                        <th class="p-3.5">EXPIRES</th>
                        <th class="p-3.5">CONTACT</th>
                        <th class="p-3.5 text-right">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300 font-mono">
                    @forelse($members as $m)
                    @php
                        $sub = $m->latestSubscription;
                        $pkg = $sub ? $sub->package : null;
                        $isSubActive = $sub && $sub->status === 'active' && \Carbon\Carbon::parse($sub->end_time)->isFuture();
                        $isDaily = $isSubActive && $pkg && ($pkg->plan_type === 'daily' || $pkg->duration_in_days <= 1);
                        $isLongTerm = $isSubActive && $pkg && ($pkg->duration_in_days >= 28 || in_array($pkg->plan_type, ['monthly', 'quarterly', 'yearly', 'annual']));
                    @endphp
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="p-3.5 text-emerald-400 font-bold">{{ $m->member_code }}</td>
                        <td class="p-3.5 font-sans font-bold text-white">
                            {{ $m->customer ? $m->customer->full_name : 'No profile' }}
                            @if($m->membership_status === 'suspended' && $m->suspension_reason)
                                <span class="block text-[10px] text-rose-400 font-normal italic font-sans">
                                    Reason: {{ $m->suspension_reason }}
                                </span>
                            @endif
                        </td>
                        
                        <!-- 1. ACCOUNT VERIFICATION STATUS (Pending vs Verified) -->
                        <td class="p-3.5 font-sans">
                            @if(($m->verification_status ?? 'pending') === 'verified')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-sky-950 text-sky-400 border border-sky-500/30">VERIFIED</span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-amber-950 text-amber-400 border border-amber-800/30">PENDING</span>
                            @endif
                        </td>
                        
                        <!-- 2. SPECIFIC PASS ACCESS STATUS -->
                        <td class="p-3.5 font-sans">
                            @if($m->membership_status === 'suspended')
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-rose-950 text-rose-300 border border-rose-800/40">SUSPENDED</span>
                            @elseif($isDaily)
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-emerald-950 text-emerald-400 border border-emerald-500/30 uppercase">
                                    ACTIVE (DAILY PASS)
                                </span>
                            @elseif($isSubActive && $pkg)
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-emerald-950 text-emerald-400 border border-emerald-500/30 uppercase">
                                    ACTIVE ({{ strtoupper($pkg->plan_type) }})
                                </span>
                            @else
                                <span class="px-2 py-0.5 text-[10px] rounded font-bold bg-slate-800 text-slate-400 border border-slate-700">NO ACTIVE PASS</span>
                            @endif
                        </td>

                        <td class="p-3.5 text-emerald-400 font-bold">{{ $m->reward_points ?? 0 }} PTS</td>
                        
                        <!-- 3. EXPIRATION DATE -->
                        <td class="p-3.5">
                            @if($isDaily && $sub)
                                <span class="text-white">{{ $sub->end_time->format('Y-m-d') }}</span>
                                <span class="text-emerald-400 text-[10px] block font-sans font-semibold">(End of Day)</span>
                            @elseif($isSubActive && $sub)
                                <span class="text-white">{{ $sub->end_time->format('Y-m-d') }}</span>
                            @else
                                <span class="text-slate-500 font-sans italic text-[11px]">No active subscription</span>
                            @endif
                        </td>

                        <td class="p-3.5">{{ $m->customer ? $m->customer->contact_number : '—' }}</td>
                        <td class="p-3.5 text-right font-sans space-x-2">
                            @if(($m->verification_status ?? 'pending') === 'pending')
                                <form action="{{ route('owner.members.approve', $m->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs px-2.5 py-1 rounded-lg">
                                        Approve
                                    </button>
                                </form>
                            @endif
                            <button onclick="openEditMemberModal(
                                {{ json_encode($m) }}, 
                                {{ json_encode($m->customer) }}, 
                                '{{ $sub && $isSubActive ? $sub->end_time->format('Y-m-d') : '' }}',
                                '{{ $pkg ? addslashes($pkg->name) : 'No active subscription' }}',
                                '{{ $pkg ? ucfirst($pkg->plan_type) : 'None' }}',
                                '{{ $sub ? $sub->start_time->format('M d, Y H:i') : 'N/A' }}',
                                {{ $isLongTerm ? 1 : 0 }}
                            )" class="text-xs text-slate-400 hover:text-white underline">
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

<!-- Modal: Add Pre-Approved Member -->
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
        <div>
            <label class="block text-slate-400 mb-1">Default Password *</label>
            <input type="text" name="password" value="pass123" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono">
            <p class="text-[10px] text-slate-500 mt-0.5">The member will use this password to sign in.</p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">Contact Phone *</label>
                <input type="text" name="contact_number" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Birthdate</label>
                <input type="date" name="date_of_birth" onclick="this.showPicker()" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono cursor-pointer">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Initial Plan (Registers Revenue & Activates Pass)</label>
            <select name="package_id" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
                <option value="">No Initial Pass</option>
                @foreach($packages as $pkg)
                    <option value="{{ $pkg->id }}">{{ $pkg->name }} (₱{{ number_format($pkg->price) }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Home Address</label>
            <input type="text" name="address" placeholder="Toril, Davao City" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
        </div>
        <div class="bg-emerald-950/40 border border-emerald-500/20 p-2.5 rounded-lg text-[11px] text-emerald-300 flex items-center space-x-2">
            <span>✓</span>
            <span>Enrolled with <strong>VERIFIED</strong> account status, immediate pass activation, and recorded revenue.</span>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('addMemberModal').close()" class="px-3 py-1.5 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-lg uppercase font-heading">Enroll & Verify</button>
        </div>
    </form>
</dialog>

<!-- Modal: Edit Member -->
<dialog id="editMemberModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-lg w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <div>
            <span class="text-[10px] uppercase tracking-widest text-emerald-400 font-heading font-bold">MEMBER DETAILS</span>
            <h3 id="edit_member_title" class="text-sm font-bold text-white"></h3>
        </div>
        <button onclick="document.getElementById('editMemberModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    
    <form id="editMemberForm" method="POST" class="space-y-4 text-xs">
        @csrf

        <!-- Read-Only Subscription Panel -->
        <div class="bg-slate-950/80 border border-slate-800 rounded-xl p-3.5 space-y-2 font-sans">
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-heading font-bold block">CURRENT SUBSCRIPTION DETAILS (READ-ONLY)</span>
            <div class="grid grid-cols-2 gap-2 text-slate-300">
                <div>
                    <span class="text-[11px] text-slate-500 block">Specific Pass Name:</span>
                    <strong id="display_pass_name" class="text-white text-xs"></strong>
                </div>
                <div>
                    <span class="text-[11px] text-slate-500 block">Pass Tier:</span>
                    <strong id="display_pass_tier" class="text-emerald-400 text-xs"></strong>
                </div>
                <div class="col-span-2">
                    <span class="text-[11px] text-slate-500 block">Pass Started On:</span>
                    <span id="display_pass_start" class="text-slate-200 font-mono text-[11px]"></span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1">First Name *</label>
                <input type="text" name="first_name" id="edit_first_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
            </div>
            <div>
                <label class="block text-slate-400 mb-1">Last Name *</label>
                <input type="text" name="last_name" id="edit_last_name" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1">Contact Phone</label>
            <input type="text" name="contact_number" id="edit_contact" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono">
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Verification Status</label>
                <select name="verification_status" id="edit_verification_status" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-semibold">
                    <option value="verified">Verified</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Pass Access Status</label>
                <select name="membership_status" id="edit_status" onchange="toggleSuspensionField(this.value)" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-semibold">
                    <option value="active">Active Pass</option>
                    <option value="expired">Expired / No Pass</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Plan Expiry Date</label>
            <input type="date" name="end_date" id="edit_end_date" onclick="this.showPicker()" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2 text-white font-mono cursor-pointer">
            <p id="lock_reason_text" class="text-[10px] text-amber-400 mt-1 hidden">
                * Locked: Pass access and expiry date cannot be modified for daily passes or members without an active monthly/longer subscription.
            </p>
        </div>

        <div id="suspension_reason_box" class="hidden">
            <label class="block text-rose-400 mb-1 font-semibold">Suspension Reason / Comments *</label>
            <textarea name="suspension_reason" id="edit_suspension_reason" rows="2" placeholder="e.g. Broken gym rules, damage to equipment, payment disputes..." class="w-full bg-slate-950 border border-rose-900/60 rounded p-2 text-white"></textarea>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('editMemberModal').close()" class="px-3 py-1.5 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-emerald-500 text-slate-950 font-extrabold rounded uppercase font-heading">Update Member</button>
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

function toggleSuspensionField(val) {
    const box = document.getElementById('suspension_reason_box');
    if (val === 'suspended') {
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
}

function openEditMemberModal(member, customer, endDate, passName, passTier, passStart, isLongTerm) {
    document.getElementById('edit_member_title').innerText = `${customer ? customer.first_name + ' ' + customer.last_name : 'Member'} (${member.member_code})`;
    document.getElementById('edit_first_name').value = customer ? customer.first_name : '';
    document.getElementById('edit_last_name').value = customer ? customer.last_name : '';
    document.getElementById('edit_contact').value = customer ? customer.contact_number : '';
    
    document.getElementById('display_pass_name').innerText = passName;
    document.getElementById('display_pass_tier').innerText = passTier;
    document.getElementById('display_pass_start').innerText = passStart;

    document.getElementById('edit_verification_status').value = member.verification_status || 'pending';
    document.getElementById('edit_status').value = member.membership_status || 'active';
    document.getElementById('edit_end_date').value = endDate || '';
    document.getElementById('edit_suspension_reason').value = member.suspension_reason || '';
    
    toggleSuspensionField(member.membership_status);

    const statusSelect = document.getElementById('edit_status');
    const endDateInput = document.getElementById('edit_end_date');
    const lockText = document.getElementById('lock_reason_text');

    // Rule: Daily or inactive memberships cannot have status/expiry modified manually
    if (isLongTerm === 0) {
        statusSelect.disabled = true;
        statusSelect.classList.add('opacity-50', 'cursor-not-allowed');
        endDateInput.disabled = true;
        endDateInput.classList.add('opacity-50', 'cursor-not-allowed');
        lockText.classList.remove('hidden');
    } else {
        statusSelect.disabled = false;
        statusSelect.classList.remove('opacity-50', 'cursor-not-allowed');
        endDateInput.disabled = false;
        endDateInput.classList.remove('opacity-50', 'cursor-not-allowed');
        lockText.classList.add('hidden');
    }

    document.getElementById('editMemberForm').action = `/owner/members/${member.id}/update`;
    document.getElementById('editMemberModal').showModal();
}
</script>
@endpush
@endsection