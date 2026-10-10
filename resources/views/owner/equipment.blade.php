@extends('layouts.app')

@section('content')
<div class="space-y-6">
    @include('owner._top_stats')
    @include('owner._navigation')

    <div class="space-y-4">
        <!-- Top Action Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
            <div>
                <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">EQUIPMENT & INDIVIDUAL UNIT TRACKER</h3>
                <p class="text-xs text-slate-400">Track and maintain specific physical barbells, plates, and machines individually.</p>
            </div>
            <button onclick="document.getElementById('addEquipmentModal').showModal()" 
                class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold px-4 py-2 rounded-xl text-xs uppercase font-heading transition shadow-lg shadow-emerald-500/20">
                + Add Equipment
            </button>
        </div>

        <!-- Inventory Table with Specific Unit Breakdowns -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-x-auto shadow-md">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-heading text-[10px]">
                    <tr>
                        <th class="p-3.5">MODEL CODE</th>
                        <th class="p-3.5">NAME & CATEGORY</th>
                        <th class="p-3.5">LOCATION</th>
                        <th class="p-3.5">TOTAL UNITS</th>
                        <th class="p-3.5">SPECIFIC PHYSICAL UNITS & STATUS</th>
                        <th class="p-3.5 text-right">ACTION</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300 font-mono">
                    @forelse($equipments as $eq)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="p-3.5 text-slate-400 font-bold">{{ $eq->equipment_code }}</td>
                        <td class="p-3.5">
                            <span class="font-sans font-bold text-white text-sm block">{{ $eq->name }}</span>
                            <span class="text-[11px] font-sans text-slate-400">{{ $eq->category ? $eq->category->name : 'General' }}</span>
                        </td>
                        <td class="p-3.5 font-sans">{{ $eq->location_in_gym }}</td>
                        <td class="p-3.5 font-bold text-white">{{ $eq->units->count() }}</td>
                        <td class="p-3.5">
                            <div class="flex flex-wrap gap-1.5 font-sans">
                                @forelse($eq->units as $unit)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono border flex items-center space-x-1
                                        @if($unit->status === 'operational') bg-emerald-950/80 text-emerald-400 border-emerald-500/30
                                        @elseif($unit->status === 'under_repair') bg-rose-950/80 text-rose-400 border-rose-800/40
                                        @else bg-amber-950/80 text-amber-400 border-amber-800/40 @endif"
                                        title="{{ $unit->unit_label }} (Status: {{ strtoupper($unit->status) }})">
                                        <span>{{ $unit->unit_code }}</span>
                                        <span class="text-[9px] uppercase font-bold">({{ substr($unit->status, 0, 3) }})</span>
                                    </span>
                                @empty
                                    <span class="text-slate-500 text-xs italic font-sans">No units generated</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="p-3.5 text-right font-sans">
                            <button onclick="openUnitMaintenanceModal({{ json_encode($eq) }}, {{ json_encode($eq->units) }})" 
                                class="text-xs bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-slate-700 px-3 py-1 rounded-lg transition font-medium">
                                Maintain Specific Unit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-slate-500 font-sans italic">No equipment logged yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Maintain Specific Unit Item Code (Expanded to max-w-2xl Roomy Layout) -->
<dialog id="unitMaintenanceModal" class="bg-slate-900 border border-slate-800 text-white p-6 sm:p-8 rounded-2xl max-w-2xl w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-5">
        <div>
            <span class="text-[11px] uppercase tracking-widest text-emerald-400 font-heading font-bold">UNIT-LEVEL SERVICE</span>
            <h3 class="text-base font-bold text-white">Log Maintenance: <span id="m_equipment_name" class="text-emerald-400"></span></h3>
        </div>
        <button onclick="document.getElementById('unitMaintenanceModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    
    <form id="unitMaintenanceForm" method="POST" class="space-y-4 text-xs">
        @csrf

        <!-- Wide Specific Unit Dropdown -->
        <div>
            <label class="block text-slate-300 mb-1 font-semibold text-xs">Select Specific Physical Unit *</label>
            <select name="equipment_unit_id" id="m_unit_select" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-sm text-white font-mono focus:border-emerald-500 outline-none">
                <!-- Dynamically populated without cutoff -->
            </select>
            <p class="text-[11px] text-slate-400 mt-1">Only the selected unit will change status; others remain in their current state.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-slate-300 mb-1 font-semibold">Maintenance Date *</label>
                <input type="date" name="maintenance_date" value="{{ date('Y-m-d') }}" onclick="this.showPicker()" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono cursor-pointer">
            </div>
            <div>
                <label class="block text-slate-300 mb-1 font-semibold">Cost (₱)</label>
                <input type="number" step="0.01" name="cost" value="0.00" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-slate-300 mb-1 font-semibold">Facility / Location</label>
                <input type="text" name="facility_or_location" placeholder="e.g. Free Weights Zone, Lifting Platform" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white">
            </div>
            <div>
                <label class="block text-slate-300 mb-1 font-semibold">Technician / Serviced By</label>
                <input type="text" name="technician_name" placeholder="e.g. Owner, Davao Gym Tech" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white">
            </div>
        </div>

        <div>
            <label class="block text-slate-300 mb-1 font-semibold">Work Done / Defect Description</label>
            <textarea name="work_done" rows="2" placeholder="e.g. Replaced right sleeve bushing on Barbell #2..." class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-white"></textarea>
        </div>

        <div>
            <label class="block text-slate-300 mb-1 font-semibold text-xs">New Status for This Unit *</label>
            <select name="new_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-white font-medium text-xs">
                <option value="under_repair">Under Repair (Take this unit out of service)</option>
                <option value="needs_maintenance">Needs Maintenance (Flagged)</option>
                <option value="operational">Operational (Fixed / Ready for use)</option>
                <option value="retired">Retired / Discarded</option>
            </select>
        </div>

        <div class="flex justify-end gap-2 pt-4 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('unitMaintenanceModal').close()" class="px-4 py-2 border border-slate-800 text-slate-300 rounded-xl">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-xl uppercase font-heading transition">
                Update Specific Unit
            </button>
        </div>
    </form>
</dialog>

<!-- Modal: Add New Equipment Group -->
<dialog id="addEquipmentModal" class="bg-slate-900 border border-slate-800 text-white p-6 rounded-2xl max-w-md w-full shadow-2xl backdrop:bg-black/80">
    <div class="flex justify-between items-center pb-3 border-b border-slate-800 mb-4">
        <h3 class="text-sm font-heading font-extrabold uppercase tracking-wider text-white">+ Add Equipment (Auto-Unit Codes)</h3>
        <button onclick="document.getElementById('addEquipmentModal').close()" class="text-slate-400 hover:text-white">✕</button>
    </div>
    <form action="{{ route('owner.equipment.store') }}" method="POST" class="space-y-3.5 text-xs">
        @csrf
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Equipment Name *</label>
            <input type="text" name="name" placeholder="e.g. Olympic Barbell (20kg)" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Category</label>
                <select name="category_id" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-slate-400 mb-1 font-semibold">Total Quantity</label>
                <input type="number" name="quantity" value="1" min="1" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white font-mono">
            </div>
        </div>
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Location in Gym</label>
            <input type="text" name="location_in_gym" placeholder="e.g. Free Weights Area" required class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
        </div>
        <div>
            <label class="block text-slate-400 mb-1 font-semibold">Initial Status</label>
            <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-lg p-2.5 text-white">
                <option value="operational">Operational</option>
                <option value="needs_maintenance">Needs Maintenance</option>
                <option value="under_repair">Under Repair</option>
            </select>
        </div>
        <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
            <button type="button" onclick="document.getElementById('addEquipmentModal').close()" class="px-3.5 py-2 border border-slate-800 text-slate-300 rounded-lg">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold rounded-lg uppercase font-heading">Save Equipment</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
function openUnitMaintenanceModal(equipment, units) {
    document.getElementById('m_equipment_name').innerText = equipment.name;
    document.getElementById('unitMaintenanceForm').action = `/owner/equipment/${equipment.id}/maintenance`;

    const select = document.getElementById('m_unit_select');
    select.innerHTML = '';

    units.forEach(unit => {
        const option = document.createElement('option');
        option.value = unit.id;
        option.text = `${unit.unit_code} - ${unit.unit_label}  |  Current Status: ${unit.status.toUpperCase()}`;
        select.appendChild(option);
    });

    document.getElementById('unitMaintenanceModal').showModal();
}
</script>
@endpush
@endsection