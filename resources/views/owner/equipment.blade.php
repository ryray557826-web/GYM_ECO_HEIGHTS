@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <!-- Warning Badges -->
        <div class="flex space-x-3">
            <span class="border border-yellow-500/40 bg-yellow-950/40 text-yellow-400 text-xs px-3 py-1 rounded-full flex items-center space-x-1.5">
                <span>⚠️</span><span>{{ $equipments->where('status', 'needs_maintenance')->count() }} item(s) need maintenance</span>
            </span>
            <span class="border border-red-500/40 bg-red-950/40 text-red-400 text-xs px-3 py-1 rounded-full flex items-center space-x-1.5">
                <span>🔧</span><span>{{ $equipments->where('status', 'under_repair')->count() }} item(s) under repair</span>
            </span>
        </div>

        <div class="flex justify-between items-center gap-3">
            <input type="text" id="equipment_search" placeholder="Search by name, category, or location..." onkeyup="filterEquipmentTable()"
                class="flex-1 bg-[#0b1120] border border-[#1e293b] rounded-lg px-4 py-2 text-xs text-white focus:outline-none">
            <button onclick="document.getElementById('addEquipmentModal').showModal()" 
                class="bg-[#76c800] hover:bg-[#68b000] text-black font-extrabold px-4 py-2 rounded text-xs uppercase font-heading">
                + Add Equipment
            </button>
        </div>

        <div class="bg-[#0f172a] border border-[#1e293b] rounded-xl overflow-x-auto shadow">
            <table class="w-full text-left text-xs" id="equipment_table">
                <thead class="bg-[#080d1a] text-gray-400 uppercase font-heading text-[11px]">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">NAME</th>
                        <th class="p-3">CATEGORY</th>
                        <th class="p-3">QTY</th>
                        <th class="p-3">LOCATION</th>
                        <th class="p-3">STATUS</th>
                        <th class="p-3">NEXT DUE</th>
                        <th class="p-3 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e293b] text-gray-300 font-mono">
                    @forelse($equipments as $eq)
                    <tr>
                        <td class="p-3 text-gray-400">{{ $eq->equipment_code }}</td>
                        <td class="p-3 font-sans font-bold text-white">{{ $eq->name }}</td>
                        <td class="p-3 font-sans">{{ $eq->category ? $eq->category->name : 'General' }}</td>
                        <td class="p-3">{{ $eq->quantity }}</td>
                        <td class="p-3 font-sans">{{ $eq->location_in_gym }}</td>
                        <td class="p-3 font-sans">
                            @if($eq->status === 'operational')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-emerald-950 text-[#76c800]">OPERATIONAL</span>
                            @elseif($eq->status === 'needs_maintenance')
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-yellow-950 text-yellow-400">NEEDS MAINTENANCE</span>
                            @else
                                <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-red-950 text-red-400">{{ strtoupper(str_replace('_', ' ', $eq->status)) }}</span>
                            @endif
                        </td>
                        <td class="p-3">{{ $eq->next_maintenance_due ?? '2026-11-10' }}</td>
                        <td class="p-3 text-right font-sans">
                            <button onclick="openMaintenanceModal({{ $eq->id }}, '{{ $eq->name }}')" 
                                class="text-xs border border-gray-700 px-2 py-0.5 rounded hover:bg-gray-800 text-gray-300">
                                Maintain
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500 font-sans">No equipment registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Equipment -->
<dialog id="addEquipmentModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">+ Add Gym Equipment</h3>
        <button onclick="document.getElementById('addEquipmentModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.equipment.store') }}" method="POST" class="space-y-3 text-xs">
        @csrf
        <div>
            <label class="block text-gray-400 mb-1">Equipment Name *</label>
            <input type="text" name="name" placeholder="e.g. Olympic Flat Bench Press" required 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Category</label>
                <select name="category_id" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required 
                    class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Location in Gym</label>
            <input type="text" name="location_in_gym" placeholder="e.g. Free Weights Zone, Cardio Deck" required 
                class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Status</label>
            <select name="status" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                <option value="operational">Operational</option>
                <option value="needs_maintenance">Needs Maintenance</option>
                <option value="under_repair">Under Repair</option>
            </select>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('addEquipmentModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Save Equipment</button>
        </div>
    </form>
</dialog>

<!-- Modal: Log Maintenance -->
<dialog id="maintenanceModal" class="bg-[#0f172a] border border-[#1e293b] text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-[#1e293b] mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">Log Maintenance: <span id="m_eq_name" class="text-[#76c800]"></span></h3>
        <button onclick="document.getElementById('maintenanceModal').close()" class="text-gray-400 hover:text-white">✕</button>
    </div>
    <form id="maintenanceForm" method="POST" class="space-y-3 text-xs">
        @csrf
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-gray-400 mb-1">Date *</label>
                <input type="date" name="maintenance_date" value="{{ date('Y-m-d') }}" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
            <div>
                <label class="block text-gray-400 mb-1">Cost (₱)</label>
                <input type="number" step="0.01" name="cost" value="0.00" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Facility / Location</label>
            <input type="text" name="facility_or_location" placeholder="e.g. Toril Main Floor" required class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Technician / Serviced By</label>
            <input type="text" name="technician_name" placeholder="e.g. Davao Gym Mechanic, Owner" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
        </div>
        <div>
            <label class="block text-gray-400 mb-1">Work Done</label>
            <textarea name="work_done" rows="2" placeholder="Replaced cable wire and adjusted bolts..." class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white"></textarea>
        </div>
        <div>
            <label class="block text-gray-400 mb-1">New Operational Status</label>
            <select name="new_status" class="w-full bg-[#080d1a] border border-[#1e293b] rounded p-2 text-white">
                <option value="operational">Operational</option>
                <option value="needs_maintenance">Needs Maintenance</option>
                <option value="under_repair">Under Repair</option>
            </select>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-[#1e293b]">
            <button type="button" onclick="document.getElementById('maintenanceModal').close()" class="px-3 py-1.5 border border-gray-800 text-gray-300 rounded">Cancel</button>
            <button type="submit" class="px-4 py-1.5 bg-[#76c800] text-black font-extrabold rounded uppercase font-heading">Save Record</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
function filterEquipmentTable() {
    const input = document.getElementById('equipment_search').value.toLowerCase();
    document.querySelectorAll('#equipment_table tbody tr').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(input) ? '' : 'none';
    });
}

function openMaintenanceModal(id, name) {
    document.getElementById('m_eq_name').innerText = name;
    document.getElementById('maintenanceForm').action = `/owner/equipment/${id}/maintenance`;
    document.getElementById('maintenanceModal').showModal();
}
</script>
@endpush
@endsection