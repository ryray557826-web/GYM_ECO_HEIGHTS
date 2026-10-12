<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Employee;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\PaymentMethod;
use App\Models\Budget;
use App\Models\EquipmentCategory;
use App\Models\Equipment;
use App\Models\EquipmentUnit;
use App\Models\AuditLog;
use App\Models\Announcement;
use App\Services\FirebaseService;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Firebase Service Instance
        $firebase = null;
        try {
            $firebase = new FirebaseService();
        } catch (\Exception $e) {
            // Fail gracefully if offline
        }

        // 2. Owner Account
        $ownerData = [
            'email' => 'owner@ecoheights.com',
            'password' => Hash::make('pass123'),
            'email_verified_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('users', 'name')) $ownerData['name'] = 'Eco Heights Gym Owner';
        if (Schema::hasColumn('users', 'role')) $ownerData['role'] = 'owner';
        if (Schema::hasColumn('users', 'account_status')) $ownerData['account_status'] = 'active';

        $ownerUser = User::firstOrCreate(['email' => 'owner@ecoheights.com'], $ownerData);

        Employee::firstOrCreate(['employee_code' => 'EMP-001'], [
            'user_id'        => $ownerUser->id,
            'first_name'     => 'Eco Heights',
            'last_name'      => 'Owner',
            'position'       => 'owner',
            'contact_number' => '0917-000-0000',
            'hire_date'      => Carbon::parse('2025-11-18'),
            'status'         => 'active',
        ]);

        // 3. Payment Methods
        PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Physical / Walk-In', 'is_online' => false, 'requires_reference' => false]);
        PaymentMethod::firstOrCreate(['code' => 'gcash'], ['name' => 'GCash', 'is_online' => true, 'requires_reference' => true]);
        PaymentMethod::firstOrCreate(['code' => 'maya'], ['name' => 'Maya', 'is_online' => true, 'requires_reference' => true]);
        PaymentMethod::firstOrCreate(['code' => 'bank_transfer'], ['name' => 'Bank Transfer', 'is_online' => true, 'requires_reference' => true]);

        // 4. Packages (Monthly, Quarterly, Yearly, Daily)
        $packagesList = [
            [
                'code' => 'PKG-MTH-750', 'name' => 'Monthly Standard Access', 'type' => 'monthly',
                'price' => 750.00, 'days' => 30, 'feature' => 'Unlimited Gym Access (6:00 AM - 11:00 PM)'
            ],
            [
                'code' => 'PKG-QTR-2100', 'name' => 'Quarterly Pass (3 Months)', 'type' => 'quarterly',
                'price' => 2100.00, 'days' => 90, 'feature' => 'Full 3-Month Access with ₱150 Savings'
            ],
            [
                'code' => 'PKG-YRL-7500', 'name' => 'Yearly VIP Pass (12 Months)', 'type' => 'yearly',
                'price' => 7500.00, 'days' => 365, 'feature' => 'VIP 12-Month Access with 2 Free Months (Save ₱1,500)'
            ],
            [
                'code' => 'PKG-DAY-050', 'name' => 'Daily Walk-In Session', 'type' => 'daily',
                'price' => 50.00, 'days' => 1, 'feature' => 'Single Day Gym Floor Pass'
            ]
        ];

        foreach ($packagesList as $pkg) {
            $p = Package::firstOrCreate(['package_code' => $pkg['code']], [
                'name'             => $pkg['name'],
                'plan_type'        => $pkg['type'],
                'price'            => $pkg['price'],
                'duration_in_days' => $pkg['days'],
                'is_active'        => true,
            ]);
            PackageFeature::firstOrCreate(['package_id' => $p->id], ['feature_description' => $pkg['feature']]);

            if ($firebase) {
                try {
                    $firebase->setDocument('packages', $pkg['code'], [
                        'package_code'     => $pkg['code'],
                        'name'             => $pkg['name'],
                        'price'            => (float) $pkg['price'],
                        'duration_in_days' => (int) $pkg['days'],
                        'plan_type'        => $pkg['type'],
                    ]);
                } catch (\Exception $e) {}
            }
        }

        // 5. Budgets
        Budget::firstOrCreate(['budget_code' => 'BUD-2026-EQP'], [
            'category_name'    => 'Equipment Upkeep & Purchases',
            'fiscal_year'      => 2026,
            'allocated_amount' => 50000.00,
            'spent_amount'     => 0.00,
        ]);

        Budget::firstOrCreate(['budget_code' => 'BUD-2026-OPS'], [
            'category_name'    => 'Facility Operations & Rent',
            'fiscal_year'      => 2026,
            'allocated_amount' => 35000.00,
            'spent_amount'     => 0.00,
        ]);

        // 6. Equipment Categories & Units
        $catWeights = EquipmentCategory::firstOrCreate(['category_code' => 'CAT-FW'], ['name' => 'Free Weights', 'description' => 'Plates, Dumbbells, Barbells']);
        $catRacks   = EquipmentCategory::firstOrCreate(['category_code' => 'CAT-RF'], ['name' => 'Racks & Frames', 'description' => 'Power cages, Squat racks']);
        $catCardio  = EquipmentCategory::firstOrCreate(['category_code' => 'CAT-CRD'], ['name' => 'Cardio', 'description' => 'Treadmills, Rowers, Bikes']);
        $catMachine = EquipmentCategory::firstOrCreate(['category_code' => 'CAT-MCH'], ['name' => 'Machines', 'description' => 'Cable stations, Leg presses']);
        $catBody    = EquipmentCategory::firstOrCreate(['category_code' => 'CAT-BW'], ['name' => 'Bodyweight', 'description' => 'Pull-up bars, Dip stations']);

        $equipmentsData = [
            ['code' => 'EQP-001', 'name' => 'Olympic Barbell (20kg)', 'category_id' => $catWeights->id, 'cat_name' => 'Free Weights', 'qty' => 4, 'loc' => 'Free Weights Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-10'],
            ['code' => 'EQP-002', 'name' => 'Dumbbell Set (5–50kg)', 'category_id' => $catWeights->id, 'cat_name' => 'Free Weights', 'qty' => 1, 'loc' => 'Free Weights Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-10-20'],
            ['code' => 'EQP-003', 'name' => 'Squat Rack', 'category_id' => $catRacks->id, 'cat_name' => 'Racks & Frames', 'qty' => 2, 'loc' => 'Lifting Platform', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-12-02'],
            ['code' => 'EQP-004', 'name' => 'Treadmill (Commercial)', 'category_id' => $catCardio->id, 'cat_name' => 'Cardio', 'qty' => 3, 'loc' => 'Cardio Zone', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-09-18'],
            ['code' => 'EQP-005', 'name' => 'Stationary Bike', 'category_id' => $catCardio->id, 'cat_name' => 'Cardio', 'qty' => 2, 'loc' => 'Cardio Zone', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-25'],
            ['code' => 'EQP-006', 'name' => 'Cable Machine (Dual Stack)', 'category_id' => $catMachine->id, 'cat_name' => 'Machines', 'qty' => 1, 'loc' => 'Machine Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-09-20'],
            ['code' => 'EQP-007', 'name' => 'Leg Press Machine', 'category_id' => $catMachine->id, 'cat_name' => 'Machines', 'qty' => 1, 'loc' => 'Machine Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-15'],
            ['code' => 'EQP-008', 'name' => 'Rowing Machine', 'category_id' => $catCardio->id, 'cat_name' => 'Cardio', 'qty' => 1, 'loc' => 'Cardio Zone', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-12-01'],
            ['code' => 'EQP-009', 'name' => 'Pull-up / Dip Station', 'category_id' => $catBody->id, 'cat_name' => 'Bodyweight', 'qty' => 2, 'loc' => 'Functional Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-10-30'],
        ];

        foreach ($equipmentsData as $ed) {
            $eq = Equipment::firstOrCreate(['equipment_code' => $ed['code']], [
                'category_id'          => $ed['category_id'],
                'name'                 => $ed['name'],
                'quantity'             => $ed['qty'],
                'location_in_gym'      => $ed['loc'],
                'status'               => $ed['status'],
                'purchase_date'        => Carbon::parse($ed['p_date']),
                'next_maintenance_due' => Carbon::parse($ed['next']),
            ]);

            for ($u = 1; $u <= $ed['qty']; $u++) {
                EquipmentUnit::firstOrCreate(['unit_code' => "{$ed['code']}-U{$u}"], [
                    'equipment_id'         => $eq->id,
                    'unit_label'           => "{$ed['name']} #{$u}",
                    'status'               => $ed['status'],
                    'next_maintenance_due' => Carbon::parse($ed['next']),
                ]);
            }
        }

        // 7. Announcements
        $announcementsList = [
            [
                'title'   => 'Extended Gym Hours Starting October',
                'badge'   => 'SCHEDULE',
                'message' => 'We are extending operating hours from 6:00 AM to 11:00 PM on weekdays.',
                'date'    => Carbon::today(),
            ],
            [
                'title'   => 'Welcome to Eco Heights Fitness Gym',
                'badge'   => 'INFO',
                'message' => 'Operating hours: 6:00 AM – 11:00 PM daily. Please re-rack your weights after use.',
                'date'    => Carbon::today(),
            ],
        ];

        foreach ($announcementsList as $anc) {
            Announcement::firstOrCreate(['title' => $anc['title']], [
                'badge'             => $anc['badge'],
                'message'           => $anc['message'],
                'posted_date'       => $anc['date'],
                'posted_by_user_id' => $ownerUser->id,
            ]);
        }

        // 8. Initial Audit Log
        AuditLog::firstOrCreate(['log_code' => 'AUD-001'], [
            'user_id'         => $ownerUser->id,
            'action'          => 'System Initialized in Production Mode',
            'entity_type'     => User::class,
            'entity_id'       => $ownerUser->id,
            'validity_period' => 'Permanent',
            'performed_by'    => 'System',
            'created_at'      => Carbon::now(),
        ]);

        Schema::enableForeignKeyConstraints();
    }
}