<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentUnit;
use App\Models\EquipmentMaintenance;
use App\Models\Expense;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::with(['category', 'units', 'latestMaintenance'])->get();
        $categories = EquipmentCategory::all();

        return view('owner.equipment', compact('equipments', 'categories'));
    }

    // 1. Create New Equipment Category Model (Generates U1..U{qty})
    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:150',
            'category_id'     => 'required|exists:equipment_categories,id',
            'quantity'        => 'required|integer|min:1',
            'location_in_gym' => 'required|string|max:100',
            'status'          => 'required|in:operational,needs_maintenance,under_repair,retired',
        ]);

        DB::transaction(function () use ($request) {
            $count = Equipment::count() + 1;
            $code = 'EQP-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $equipment = Equipment::create([
                'equipment_code'       => $code,
                'name'                 => $request->name,
                'category_id'          => $request->category_id,
                'quantity'             => $request->quantity,
                'location_in_gym'      => $request->location_in_gym,
                'status'               => $request->status,
                'purchase_date'        => Carbon::today()->toDateString(),
                'next_maintenance_due' => Carbon::today()->addMonths(3)->toDateString(),
            ]);

            // Auto-generate unit codes: EQP-xxx-U1, EQP-xxx-U2...
            for ($i = 1; $i <= $request->quantity; $i++) {
                EquipmentUnit::create([
                    'equipment_id'         => $equipment->id,
                    'unit_code'            => "{$code}-U{$i}",
                    'unit_label'           => "{$request->name} #{$i}",
                    'status'               => $request->status,
                    'next_maintenance_due' => Carbon::today()->addMonths(3)->toDateString(),
                ]);
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "New Equipment Added: {$equipment->name} ({$code}) with {$request->quantity} units",
                    'performed_by'    => 'Owner',
                    'validity_period' => 'Inventory Registration',
                ]);
            }
        });

        return back()->with('success', "Equipment registered with individual unit tracking.");
    }

    // 2. Add More Units to an ALREADY EXISTING Equipment (e.g., adding U5, U6 to existing barbells)
    public function addUnits(Request $request, Equipment $equipment)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:50',
            'status'   => 'required|in:operational,needs_maintenance,under_repair',
        ]);

        DB::transaction(function () use ($request, $equipment) {
            // Find highest existing unit suffix number to avoid collisions
            $maxNum = 0;
            foreach ($equipment->units as $u) {
                if (preg_match('/-U(\d+)$/', $u->unit_code, $matches)) {
                    $maxNum = max($maxNum, (int)$matches[1]);
                }
            }
            if ($maxNum === 0) {
                $maxNum = $equipment->units()->count();
            }

            $addedQty = (int)$request->quantity;
            for ($i = 1; $i <= $addedQty; $i++) {
                $unitNum = $maxNum + $i;
                EquipmentUnit::create([
                    'equipment_id'         => $equipment->id,
                    'unit_code'            => "{$equipment->equipment_code}-U{$unitNum}",
                    'unit_label'           => "{$equipment->name} #{$unitNum}",
                    'status'               => $request->status,
                    'next_maintenance_due' => Carbon::today()->addMonths(3)->toDateString(),
                ]);
            }

            // Update parent quantity total
            $equipment->increment('quantity', $addedQty);

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Added {$addedQty} Unit(s) to {$equipment->name} ({$equipment->equipment_code})",
                    'performed_by'    => 'Owner',
                    'validity_period' => 'Inventory Expansion',
                ]);
            }
        });

        return back()->with('success', "Added {$request->quantity} new unit(s) to {$equipment->name}.");
    }

    // 3. Maintain a Specific Physical Unit (Zero Collateral Impact on other units)
    public function storeMaintenance(Request $request, Equipment $equipment)
    {
        $request->validate([
            'equipment_unit_id'    => 'required|exists:equipment_units,id',
            'maintenance_date'     => 'required|date',
            'facility_or_location' => 'required|string|max:150',
            'cost'                 => 'required|numeric|min:0',
            'technician_name'      => 'nullable|string|max:100',
            'work_done'            => 'nullable|string',
            'new_status'           => 'required|in:operational,needs_maintenance,under_repair,retired',
        ]);

        DB::transaction(function () use ($request, $equipment) {
            $code = 'MNT-' . str_pad(EquipmentMaintenance::count() + 1, 3, '0', STR_PAD_LEFT);
            $unit = EquipmentUnit::findOrFail($request->equipment_unit_id);

            EquipmentMaintenance::create([
                'maintenance_code'     => $code,
                'equipment_id'         => $equipment->id,
                'equipment_unit_id'    => $unit->id,
                'maintenance_date'     => $request->maintenance_date,
                'facility_or_location' => $request->facility_or_location,
                'cost'                 => $request->cost,
                'technician_name'      => $request->technician_name,
                'issue_description'    => "Maintenance logged for [{$unit->unit_code}] {$unit->unit_label}",
                'work_done'            => $request->work_done,
                'status'               => 'completed',
            ]);

            // Update ONLY the selected specific unit
            $unit->update([
                'status'                => $request->new_status,
                'last_maintenance_date' => $request->maintenance_date,
            ]);

            // Synchronize parent overall status
            $hasIssues = $equipment->units()->whereIn('status', ['under_repair', 'needs_maintenance'])->exists();
            $equipment->update(['status' => $hasIssues ? 'needs_maintenance' : 'operational']);

            // If expense incurred, record in expense ledger
            if ($request->cost > 0) {
                $expCode = 'EXP-' . str_pad(Expense::count() + 1, 3, '0', STR_PAD_LEFT);
                Expense::create([
                    'expense_code'   => $expCode,
                    'category'       => 'Maintenance',
                    'description'    => "Repair on [{$unit->unit_code}] {$unit->unit_label}: " . ($request->work_done ?? 'Service repair'),
                    'amount'         => $request->cost,
                    'expense_date'   => $request->maintenance_date,
                ]);
            }
        });

        return back()->with('success', "Maintenance record saved for specific unit.");
    }

    // 4. Remove / Delete Entire Equipment Model
    public function destroy(Equipment $equipment)
    {
        DB::transaction(function () use ($equipment) {
            $name = $equipment->name;
            $code = $equipment->equipment_code;

            // Delete associated units and maintenance logs cleanly
            $equipment->units()->delete();
            $equipment->maintenances()->delete();
            $equipment->delete();

            if (class_exists(AuditLog::class)) {
                AuditLog::create([
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Removed Equipment: {$name} ({$code}) from Inventory",
                    'performed_by'    => 'Owner',
                    'validity_period' => 'Decommissioned',
                ]);
            }
        });

        return back()->with('success', "Equipment successfully removed from inventory.");
    }
}