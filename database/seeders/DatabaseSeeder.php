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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Truncate Tables Cleanly
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

        // Firebase Service Instance
        $firebase = null;
        try {
            $firebase = new FirebaseService();
        } catch (\Exception $e) {
            // Optional if running offline
        }

        // =========================================================================
        // 1. OWNER ACCOUNT ONLY
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
        // 2. PAYMENT METHODS CHANNELS
        // =========================================================================
        $cash  = PaymentMethod::create(['code' => 'cash', 'name' => 'Physical / Walk-In', 'is_online' => false, 'requires_reference' => false]);
        $gcash = PaymentMethod::create(['code' => 'gcash', 'name' => 'GCash', 'is_online' => true, 'requires_reference' => true]);
        $maya  = PaymentMethod::create(['code' => 'maya', 'name' => 'Maya', 'is_online' => true, 'requires_reference' => true]);
        $bank  = PaymentMethod::create(['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'is_online' => true, 'requires_reference' => true]);

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
        // 4. BUDGETS (Document Page 17-18)
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
        // 5. EQUIPMENT CATEGORIES & INVENTORY
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
        ];

        foreach ($equipmentsData as $ed) {
            Equipment::create([
                'equipment_code'       => $ed['code'],
                'category_id'          => $ed['category_id'],
                'name'                 => $ed['name'],
                'quantity'             => $ed['qty'],
                'location_in_gym'      => $ed['loc'],
                'status'               => $ed['status'],
                'purchase_date'        => Carbon::parse($ed['p_date']),
                'next_maintenance_due' => Carbon::parse($ed['next']),
            ]);

            if ($firebase) {
                $firebase->setDocument('equipment', $ed['code'], [
                    'equipment_code'       => $ed['code'],
                    'name'                 => $ed['name'],
                    'category'             => $ed['cat_name'],
                    'quantity'             => (int) $ed['qty'],
                    'location_in_gym'      => $ed['loc'],
                    'status'               => $ed['status'],
                    'next_maintenance_due' => $ed['next'],
                ]);
            }
        }

        // =========================================================================
        // 6. INITIAL GYM ANNOUNCEMENTS
        // =========================================================================
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
                'message' => 'Enjoy your workout sessions! Please re-rack your weights after use.',
                'date'    => Carbon::today(),
            ],
        ];

        foreach ($announcementsList as $anc) {
            Announcement::create([
                'title'             => $anc['title'],
                'badge'             => $anc['badge'],
                'message'           => $anc['message'],
                'posted_date'       => $anc['date'],
                'posted_by_user_id' => $ownerUser->id,
            ]);

            if ($firebase) {
                $firebase->addDocument('announcements', [
                    'title'       => $anc['title'],
                    'badge'       => $anc['badge'],
                    'message'     => $anc['message'],
                    'posted_date' => $anc['date']->toDateString(),
                ]);
            }
        }

        // =========================================================================
        // 7. INITIAL AUDIT LOG
        // =========================================================================
        AuditLog::create([
            'log_code'        => 'AUD-001',
            'user_id'         => $ownerUser->id,
            'action'          => 'System Initialized with Owner Account',
            'entity_type'     => User::class,
            'entity_id'       => $ownerUser->id,
            'validity_period' => 'Permanent',
            'performed_by'    => 'System',
            'created_at'      => Carbon::now(),
        ]);
    }
}