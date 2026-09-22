<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentMaintenance;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipmentController extends Controller
{
    public function index()
    {
        $equipments = Equipment::with(['category', 'latestMaintenance'])->get();
        $categories = EquipmentCategory::all();

        return view('owner.equipment', compact('equipments', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:equipment_categories,id',
            'quantity' => 'required|integer|min:1',
            'location_in_gym' => 'required|string|max:100',
            'status' => 'required|in:operational,needs_maintenance,under_repair,retired',
        ]);

        $count = Equipment::count() + 1;
        $code = 'EQP-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        Equipment::create([
            'equipment_code' => $code,
            'name' => $request->name,
            'category_id' => $request->category_id,
            'quantity' => $request->quantity,
            'location_in_gym' => $request->location_in_gym,
            'status' => $request->status,
            'purchase_date' => Carbon::today()->toDateString(),
            'next_maintenance_due' => Carbon::today()->addMonths(3)->toDateString(),
        ]);

        return back()->with('success', "Equipment [{$code}] {$request->name} added.");
    }

    public function storeMaintenance(Request $request, Equipment $equipment)
    {
        $request->validate([
            'maintenance_date' => 'required|date',
            'facility_or_location' => 'required|string|max:150',
            'cost' => 'required|numeric|min:0',
            'technician_name' => 'nullable|string|max:100',
            'work_done' => 'nullable|string',
            'new_status' => 'required|in:operational,needs_maintenance,under_repair,retired',
        ]);

        DB::transaction(function () use ($request, $equipment) {
            $code = 'MNT-' . str_pad(EquipmentMaintenance::count() + 1, 3, '0', STR_PAD_LEFT);

            $maintenance = EquipmentMaintenance::create([
                'maintenance_code' => $code,
                'equipment_id' => $equipment->id,
                'maintenance_date' => $request->maintenance_date,
                'facility_or_location' => $request->facility_or_location,
                'cost' => $request->cost,
                'technician_name' => $request->technician_name,
                'work_done' => $request->work_done,
                'status' => 'completed',
            ]);

            $equipment->update(['status' => $request->new_status]);

            if ($request->cost > 0) {
                $expCode = 'EXP-' . str_pad(Expense::count() + 1, 3, '0', STR_PAD_LEFT);
                Expense::create([
                    'expense_code' => $expCode,
                    'maintenance_id' => $maintenance->id,
                    'category' => 'Maintenance',
                    'description' => "Maintenance for {$equipment->name}: " . ($request->work_done ?? 'Service repair'),
                    'amount' => $request->cost,
                    'expense_date' => $request->maintenance_date,
                ]);
            }
        });

        return back()->with('success', "Maintenance log saved for {$equipment->name}.");
    }
}