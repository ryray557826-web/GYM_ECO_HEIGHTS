<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\PackageFeature;
use App\Models\MemberSubscription;
use App\Models\PaymentMethod;
use App\Models\Budget;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Expense;
use App\Models\EquipmentCategory;
use App\Models\Equipment;
use App\Models\EquipmentMaintenance;
use App\Models\Attendance;
use App\Models\GymNote;
use App\Models\AuditLog;
use App\Models\Announcement;
use App\Services\FirebaseService;
use App\Models\EquipmentUnit;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean Database
        Schema::disableForeignKeyConstraints();

        Announcement::truncate();
        AuditLog::truncate();
        GymNote::truncate();
        Attendance::truncate();
        Expense::truncate();
        Revenue::truncate();
        Payment::truncate();
        MemberSubscription::truncate();
        PackageFeature::truncate();
        Package::truncate();
        PaymentMethod::truncate();
        EquipmentMaintenance::truncate();
        Equipment::truncate();
        EquipmentCategory::truncate();
        Budget::truncate();
        Member::truncate();
        Customer::truncate();
        Employee::truncate();
        User::truncate();

        Schema::enableForeignKeyConstraints();

        $firebase = null;
        try {
            $firebase = new FirebaseService();
        } catch (\Exception $e) {
            // Optional if running offline
        }

        // =========================================================================
        // 1. OWNER ACCOUNT (SINGLE CREDENTIAL)
        // =========================================================================
        $ownerData = [
            'email' => 'owner@ecoheights.com',
            'password' => Hash::make('pass123'),
            'email_verified_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('users', 'name')) $ownerData['name'] = 'Eco Heights Gym Owner';
        if (Schema::hasColumn('users', 'role')) $ownerData['role'] = 'owner';
        if (Schema::hasColumn('users', 'account_status')) $ownerData['account_status'] = 'active';

        $ownerUser = User::create($ownerData);

        Employee::create([
            'user_id'        => $ownerUser->id,
            'employee_code'  => 'EMP-001',
            'first_name'     => 'Eco Heights',
            'last_name'      => 'Owner',
            'position'       => 'owner',
            'contact_number' => '0917-000-0000',
            'hire_date'      => Carbon::parse('2025-11-18'),
            'status'         => 'active',
        ]);

        // =========================================================================
        // 2. PAYMENT METHODS INFRASTRUCTURE
        // =========================================================================
        PaymentMethod::create(['code' => 'cash', 'name' => 'Physical / Walk-In', 'is_online' => false, 'requires_reference' => false]);
        PaymentMethod::create(['code' => 'gcash', 'name' => 'GCash', 'is_online' => true, 'requires_reference' => true]);
        PaymentMethod::create(['code' => 'maya', 'name' => 'Maya', 'is_online' => true, 'requires_reference' => true]);
        PaymentMethod::create(['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'is_online' => true, 'requires_reference' => true]);

        // =========================================================================
        // 3. PACKAGES (Monthly, Quarterly, Yearly, Daily)
        // =========================================================================
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
            $p = Package::create([
                'package_code'     => $pkg['code'],
                'name'             => $pkg['name'],
                'plan_type'        => $pkg['type'],
                'price'            => $pkg['price'],
                'duration_in_days' => $pkg['days'],
                'is_active'        => true,
            ]);
            PackageFeature::create(['package_id' => $p->id, 'feature_description' => $pkg['feature']]);

            if ($firebase) {
                $firebase->setDocument('packages', $pkg['code'], [
                    'package_code'     => $pkg['code'],
                    'name'             => $pkg['name'],
                    'price'            => (float) $pkg['price'],
                    'duration_in_days' => (int) $pkg['days'],
                    'plan_type'        => $pkg['type'],
                ]);
            }
        }

        // =========================================================================
        // 4. FISCAL BUDGETS
        // =========================================================================
        Budget::create([
            'budget_code'      => 'BUD-2026-EQP',
            'category_name'    => 'Equipment Upkeep & Purchases',
            'fiscal_year'      => 2026,
            'allocated_amount' => 50000.00,
            'spent_amount'     => 0.00,
        ]);

        Budget::create([
            'budget_code'      => 'BUD-2026-OPS',
            'category_name'    => 'Facility Operations & Rent',
            'fiscal_year'      => 2026,
            'allocated_amount' => 35000.00,
            'spent_amount'     => 0.00,
        ]);

        // =========================================================================
        // 5. EQUIPMENT CATEGORIES & GYM ASSETS
        // =========================================================================
        $catWeights = EquipmentCategory::create(['category_code' => 'CAT-FW', 'name' => 'Free Weights', 'description' => 'Plates, Dumbbells, Barbells']);
        $catRacks   = EquipmentCategory::create(['category_code' => 'CAT-RF', 'name' => 'Racks & Frames', 'description' => 'Power cages, Squat racks']);
        $catCardio  = EquipmentCategory::create(['category_code' => 'CAT-CRD', 'name' => 'Cardio', 'description' => 'Treadmills, Rowers, Bikes']);
        $catMachine = EquipmentCategory::create(['category_code' => 'CAT-MCH', 'name' => 'Machines', 'description' => 'Cable stations, Leg presses']);
        $catBody    = EquipmentCategory::create(['category_code' => 'CAT-BW', 'name' => 'Bodyweight', 'description' => 'Pull-up bars, Dip stations']);

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
            $eq = Equipment::create([
                'equipment_code'       => $ed['code'],
                'category_id'          => $ed['category_id'],
                'name'                 => $ed['name'],
                'quantity'             => $ed['qty'],
                'location_in_gym'      => $ed['loc'],
                'status'               => $ed['status'],
                'purchase_date'        => Carbon::parse($ed['p_date']),
                'next_maintenance_due' => Carbon::parse($ed['next']),
            ]);

            // Create individual physical unit codes (e.g. EQP-001-U1, EQP-001-U2...)
            for ($u = 1; $u <= $ed['qty']; $u++) {
                if (class_exists(EquipmentUnit::class)) {
                    EquipmentUnit::create([
                        'equipment_id'         => $eq->id,
                        'unit_code'            => "{$ed['code']}-U{$u}",
                        'unit_label'           => "{$ed['name']} #{$u}",
                        'status'               => ($u === 2 && $ed['code'] === 'EQP-001') ? 'under_repair' : $ed['status'],
                        'next_maintenance_due' => Carbon::parse($ed['next']),
                    ]);
                } else {
                    DB::table('equipment_units')->insert([
                        'equipment_id'         => $eq->id,
                        'unit_code'            => "{$ed['code']}-U{$u}",
                        'unit_label'           => "{$ed['name']} #{$u}",
                        'status'               => ($u === 2 && $ed['code'] === 'EQP-001') ? 'under_repair' : $ed['status'],
                        'next_maintenance_due' => Carbon::parse($ed['next']),
                        'created_at'           => now(),
                        'updated_at'           => now(),
                    ]);
                }
            }
        }

        // =========================================================================
        // 6. OFFICIAL WELCOME ANNOUNCEMENT
        // =========================================================================
        $announcement = Announcement::create([
            'title'             => 'Welcome to Eco Heights Fitness Gym',
            'badge'             => 'INFO',
            'message'           => 'Operating hours: 6:00 AM – 11:00 PM daily. Please re-rack your weights after use.',
            'posted_date'       => Carbon::today(),
            'posted_by_user_id' => $ownerUser->id,
        ]);

        if ($firebase) {
            $firebase->addDocument('announcements', [
                'title'       => $announcement->title,
                'badge'       => $announcement->badge,
                'message'     => $announcement->message,
                'posted_date' => $announcement->posted_date->toDateString(),
            ]);
        }

        // Initial Audit Entry
        AuditLog::create([
            'log_code'        => 'AUD-001',
            'user_id'         => $ownerUser->id,
            'action'          => 'System Initialized in Production Mode',
            'entity_type'     => User::class,
            'entity_id'       => $ownerUser->id,
            'validity_period' => 'Permanent',
            'performed_by'    => 'System',
            'created_at'      => Carbon::now(),
        ]);
    }
}