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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks for clean truncation/population
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

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

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =========================================================================
        // 1. USERS & EMPLOYEES (Owner & Staff Layer)
        // =========================================================================
        $ownerData = [
            'email' => 'owner@ecoheights.com',
            'password' => Hash::make('pass123'),
            'email_verified_at' => Carbon::now(),
        ];
        if (Schema::hasColumn('users', 'name')) $ownerData['name'] = 'Eco Heights Gym Owner';
        if (Schema::hasColumn('users', 'role')) $ownerData['role'] = 'owner';
        if (Schema::hasColumn('users', 'account_status')) $ownerData['account_status'] = 'active';
        if (Schema::hasColumn('users', 'status')) $ownerData['status'] = 'active';

        $ownerUser = User::create($ownerData);

        Employee::create([
            'user_id' => $ownerUser->id,
            'employee_code' => 'EMP-001',
            'first_name' => 'Eco Heights',
            'last_name' => 'Owner',
            'position' => 'owner',
            'contact_number' => '0917-000-0000',
            'hire_date' => Carbon::parse('2025-11-18'),
            'status' => 'active',
        ]);

        // =========================================================================
        // 2. PAYMENT METHODS LOOKUP
        // =========================================================================
        $cash = PaymentMethod::create(['code' => 'cash', 'name' => 'Physical / Walk-In', 'is_online' => false, 'requires_reference' => false]);
        $gcash = PaymentMethod::create(['code' => 'gcash', 'name' => 'GCash', 'is_online' => true, 'requires_reference' => true]);
        $maya = PaymentMethod::create(['code' => 'maya', 'name' => 'Maya', 'is_online' => true, 'requires_reference' => true]);
        $bank = PaymentMethod::create(['code' => 'bank_transfer', 'name' => 'Bank Transfer', 'is_online' => true, 'requires_reference' => true]);

        // =========================================================================
        // 3. PACKAGES & FEATURES (Monthly ₱750, Daily ₱50)
        // =========================================================================
        $pkgMonthly = Package::create([
            'package_code' => 'PKG-MTH-750',
            'name' => 'Monthly Standard Access',
            'plan_type' => 'monthly',
            'price' => 750.00,
            'duration_in_days' => 30,
            'is_active' => true,
        ]);
        PackageFeature::create(['package_id' => $pkgMonthly->id, 'feature_description' => 'Unlimited Gym Access (6:00 AM - 11:00 PM)']);
        PackageFeature::create(['package_id' => $pkgMonthly->id, 'feature_description' => 'Free Locker & Shower Access']);

        $pkgDaily = Package::create([
            'package_code' => 'PKG-DAY-050',
            'name' => 'Daily Walk-In Session',
            'plan_type' => 'daily',
            'price' => 50.00,
            'duration_in_days' => 1,
            'is_active' => true,
        ]);
        PackageFeature::create(['package_id' => $pkgDaily->id, 'feature_description' => 'Single Day Gym Floor Pass']);

        // =========================================================================
        // 4. BUDGETS (Document Page 17-18)
        // =========================================================================
        $budEquipment = Budget::create([
            'budget_code' => 'BUD-2026-EQP',
            'category_name' => 'Equipment Upkeep & Purchases',
            'fiscal_year' => 2026,
            'allocated_amount' => 50000.00,
            'spent_amount' => 5700.00,
        ]);

        $budOps = Budget::create([
            'budget_code' => 'BUD-2026-OPS',
            'category_name' => 'Facility Operations & Rent',
            'fiscal_year' => 2026,
            'allocated_amount' => 35000.00,
            'spent_amount' => 800.00,
        ]);

        // =========================================================================
        // 5. EQUIPMENT CATEGORIES & INVENTORY (Exact Match to Screenshot 8)
        // =========================================================================
        $catWeights = EquipmentCategory::create(['category_code' => 'CAT-FW', 'name' => 'Free Weights', 'description' => 'Plates, Dumbbells, Barbells']);
        $catRacks   = EquipmentCategory::create(['category_code' => 'CAT-RF', 'name' => 'Racks & Frames', 'description' => 'Power cages, Squat racks']);
        $catCardio  = EquipmentCategory::create(['category_code' => 'CAT-CRD', 'name' => 'Cardio', 'description' => 'Treadmills, Rowers, Bikes']);
        $catMachine = EquipmentCategory::create(['category_code' => 'CAT-MCH', 'name' => 'Machines', 'description' => 'Cable stations, Leg presses']);
        $catBody    = EquipmentCategory::create(['category_code' => 'CAT-BW', 'name' => 'Bodyweight', 'description' => 'Pull-up bars, Dip stations']);

        $equipmentsData = [
            ['code' => 'EQP-001', 'name' => 'Olympic Barbell (20kg)', 'category_id' => $catWeights->id, 'qty' => 4, 'loc' => 'Free Weights Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-10'],
            ['code' => 'EQP-002', 'name' => 'Dumbbell Set (5–50kg)', 'category_id' => $catWeights->id, 'qty' => 1, 'loc' => 'Free Weights Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-10-20'],
            ['code' => 'EQP-003', 'name' => 'Squat Rack', 'category_id' => $catRacks->id, 'qty' => 2, 'loc' => 'Lifting Platform', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-12-02'],
            ['code' => 'EQP-004', 'name' => 'Treadmill (Commercial)', 'category_id' => $catCardio->id, 'qty' => 3, 'loc' => 'Cardio Zone', 'status' => 'needs_maintenance', 'p_date' => '2025-11-18', 'next' => '2026-09-18'],
            ['code' => 'EQP-005', 'name' => 'Stationary Bike', 'category_id' => $catCardio->id, 'qty' => 2, 'loc' => 'Cardio Zone', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-25'],
            ['code' => 'EQP-006', 'name' => 'Cable Machine (Dual Stack)', 'category_id' => $catMachine->id, 'qty' => 1, 'loc' => 'Machine Area', 'status' => 'under_repair', 'p_date' => '2025-11-18', 'next' => '2026-09-20'],
            ['code' => 'EQP-007', 'name' => 'Leg Press Machine', 'category_id' => $catMachine->id, 'qty' => 1, 'loc' => 'Machine Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-11-15'],
            ['code' => 'EQP-008', 'name' => 'Rowing Machine', 'category_id' => $catCardio->id, 'qty' => 1, 'loc' => 'Cardio Zone', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-12-01'],
            ['code' => 'EQP-009', 'name' => 'Pull-up / Dip Station', 'category_id' => $catBody->id, 'qty' => 2, 'loc' => 'Functional Area', 'status' => 'operational', 'p_date' => '2025-11-18', 'next' => '2026-10-30'],
        ];

        $createdEquipment = [];
        foreach ($equipmentsData as $ed) {
            $createdEquipment[$ed['code']] = Equipment::create([
                'equipment_code' => $ed['code'],
                'category_id' => $ed['category_id'],
                'name' => $ed['name'],
                'quantity' => $ed['qty'],
                'location_in_gym' => $ed['loc'],
                'status' => $ed['status'],
                'purchase_date' => Carbon::parse($ed['p_date']),
                'next_maintenance_due' => Carbon::parse($ed['next']),
            ]);
        }

        // =========================================================================
        // 6. EQUIPMENT MAINTENANCE & EXPENSES (Exact Match to Screenshot 7: ₱6,500 Total)
        // =========================================================================
        $maintTreadmill = EquipmentMaintenance::create([
            'maintenance_code' => 'MNT-001',
            'equipment_id' => $createdEquipment['EQP-004']->id,
            'maintenance_date' => Carbon::parse('2026-09-02'),
            'facility_or_location' => 'Toril Main Floor',
            'cost' => 1200.00,
            'technician_name' => 'Davao Tech Services',
            'issue_description' => 'Belt slipping under load',
            'work_done' => 'Treadmill belt replacement',
            'status' => 'completed',
        ]);

        $createdEquipment['EQP-001']->maintenances()->create([
            'maintenance_code' => 'MNT-002',
            'maintenance_date' => Carbon::parse('2026-08-10'),
            'facility_or_location' => 'Free Weights Area',
            'cost' => 0.00,
            'technician_name' => 'Owner',
            'work_done' => 'Lubricated sleeve bearings',
            'status' => 'completed',
        ]);

        Expense::create([
            'expense_code' => 'EXP-001',
            'budget_id' => $budEquipment->id,
            'category' => 'Equipment',
            'description' => 'New barbell set (20kg)',
            'amount' => 4500.00,
            'expense_date' => Carbon::parse('2026-08-20'),
        ]);

        Expense::create([
            'expense_code' => 'EXP-002',
            'budget_id' => $budEquipment->id,
            'maintenance_id' => $maintTreadmill->id,
            'category' => 'Maintenance',
            'description' => 'Treadmill belt replacement',
            'amount' => 1200.00,
            'expense_date' => Carbon::parse('2026-09-02'),
        ]);

        Expense::create([
            'expense_code' => 'EXP-003',
            'budget_id' => $budOps->id,
            'category' => 'Damages',
            'description' => 'Broken dumbbell rack repair',
            'amount' => 800.00,
            'expense_date' => Carbon::parse('2026-09-10'),
        ]);

        // =========================================================================
        // 7. CUSTOMERS & MEMBERS (Exact Match to Screenshot 4)
        // =========================================================================
        $membersData = [
            [
                'code' => 'ECO-001',
                'first' => 'Maria', 'last' => 'Santos', 'email' => 'maria@gmail.com', 'phone' => '09171234567',
                'status' => 'active', 'plan' => $pkgMonthly, 'end' => '2026-10-22 05:47:00', 'sub_status' => 'active',
                'joined' => '2026-08-18'
            ],
            [
                'code' => 'ECO-002',
                'first' => 'Carlos', 'last' => 'Dela Cruz', 'email' => 'carlos@gmail.com', 'phone' => '09281234567',
                'status' => 'expired', 'plan' => $pkgMonthly, 'end' => '2026-09-19 23:59:59', 'sub_status' => 'expired',
                'joined' => '2026-07-10'
            ],
            [
                'code' => 'ECO-003',
                'first' => 'Liza', 'last' => 'Reyes', 'email' => 'liza@gmail.com', 'phone' => '09391234567',
                'status' => 'active', 'plan' => $pkgDaily, 'end' => '2026-09-23 23:59:59', 'sub_status' => 'active',
                'joined' => '2026-09-14'
            ],
            [
                'code' => 'ECO-004',
                'first' => 'Ryan', 'last' => 'Aquino', 'email' => 'ryan@gmail.com', 'phone' => '09501234567',
                'status' => 'active', 'plan' => $pkgMonthly, 'end' => '2026-10-04 23:59:59', 'sub_status' => 'active',
                'joined' => '2026-08-04'
            ],
            [
                'code' => 'ECO-005',
                'first' => 'Jenny', 'last' => 'Flores', 'email' => 'jenny@gmail.com', 'phone' => '09611234567',
                'status' => 'pending', 'plan' => $pkgMonthly, 'end' => '2026-09-22 23:59:59', 'sub_status' => 'pending',
                'joined' => '2026-09-16'
            ]
        ];

        $createdMembers = [];
        $createdSubscriptions = [];

        foreach ($membersData as $md) {
            $memberUserData = [
                'email' => $md['email'],
                'password' => Hash::make('pass123'),
                'email_verified_at' => Carbon::now(),
            ];
            if (Schema::hasColumn('users', 'name')) $memberUserData['name'] = "{$md['first']} {$md['last']}";
            if (Schema::hasColumn('users', 'role')) $memberUserData['role'] = 'member';
            if (Schema::hasColumn('users', 'account_status')) $memberUserData['account_status'] = $md['status'] === 'pending' ? 'pending' : 'active';
            if (Schema::hasColumn('users', 'status')) $memberUserData['status'] = $md['status'] === 'pending' ? 'pending' : 'active';

            $user = User::create($memberUserData);

            $customer = Customer::create([
                'user_id' => $user->id,
                'first_name' => $md['first'],
                'last_name' => $md['last'],
                'email' => $md['email'],
                'contact_number' => $md['phone'],
                'date_of_birth' => Carbon::parse('1998-05-15'),
                'age' => 28,
                'emergency_contact_name' => 'Family Contact',
                'emergency_contact_phone' => '0917-999-8888',
                'address' => 'Baliok, Toril, Davao City',
            ]);

            $memberFields = [
                'customer_id' => $customer->id,
                'member_code' => $md['code'],
                'joined_date' => Carbon::parse($md['joined']),
            ];
            if (Schema::hasColumn('members', 'membership_status')) $memberFields['membership_status'] = $md['status'];
            if (Schema::hasColumn('members', 'status')) $memberFields['status'] = $md['status'];

            $member = Member::create($memberFields);

            $subFields = [
                'member_id' => $member->id,
                'package_id' => $md['plan']->id,
                'status' => $md['sub_status'],
            ];
            if (Schema::hasColumn('member_subscriptions', 'start_time')) {
                $subFields['start_time'] = Carbon::parse($md['joined'])->startOfDay();
                $subFields['end_time'] = Carbon::parse($md['end']);
            }
            if (Schema::hasColumn('member_subscriptions', 'start_date')) {
                $subFields['start_date'] = Carbon::parse($md['joined'])->toDateString();
                $subFields['end_date'] = Carbon::parse($md['end'])->toDateString();
            }

            $sub = MemberSubscription::create($subFields);

            $createdMembers[$md['code']] = $member;
            $createdSubscriptions[$md['code']] = $sub;
        }

        // =========================================================================
        // 8. PAYMENTS & REVENUES (Sums up to EXACTLY ₱4,600 Verified Revenue)
        // =========================================================================
        $verifiedPaymentsList = [
            ['code' => 'PAY-001', 'm' => 'ECO-001', 'sub' => true,  'method' => $maya,  'amt' => 750, 'ref' => 'Maya-20260818-1001',  'date' => '2026-08-18 09:00:00'],
            ['code' => 'PAY-007', 'm' => 'ECO-001', 'sub' => false, 'method' => $gcash, 'amt' => 750, 'ref' => 'GCash-20260718-2001', 'date' => '2026-07-18 08:30:00'],
            ['code' => 'PAY-003', 'm' => 'ECO-004', 'sub' => true,  'method' => $gcash, 'amt' => 750, 'ref' => 'GCash-20260904-4001', 'date' => '2026-09-04 08:00:00'],
            ['code' => 'PAY-004', 'm' => 'ECO-002', 'sub' => false, 'method' => $cash,  'amt' => 750, 'ref' => null,                    'date' => '2026-07-10 10:30:00'],
            ['code' => 'PAY-005', 'm' => 'ECO-002', 'sub' => false, 'method' => $cash,  'amt' => 750, 'ref' => null,                    'date' => '2026-08-10 11:00:00'],
            ['code' => 'PAY-006', 'm' => 'ECO-001', 'sub' => false, 'method' => $cash,  'amt' => 700, 'ref' => null,                    'date' => '2026-06-18 10:00:00'],
            ['code' => 'PAY-008', 'm' => 'ECO-003', 'sub' => false, 'method' => $cash,  'amt' => 50,  'ref' => null,                    'date' => '2026-09-14 10:05:00'],
            ['code' => 'PAY-009', 'm' => 'ECO-003', 'sub' => false, 'method' => $cash,  'amt' => 50,  'ref' => null,                    'date' => '2026-09-15 09:20:00'],
            ['code' => 'REQ-003', 'm' => 'ECO-001', 'sub' => false, 'method' => $maya,  'amt' => 50,  'ref' => 'Maya-20260910-77341',  'date' => '2026-09-10 08:30:00'],
        ];

        $pRefs = [];
        $revIndex = 1;
        foreach ($verifiedPaymentsList as $vp) {
            $mem = $createdMembers[$vp['m']];
            $pData = [
                'payment_code' => $vp['code'],
                'customer_id' => $mem->customer_id,
                'member_id' => $mem->id,
                'payment_method_id' => $vp['method']->id,
                'amount' => $vp['amt'],
                'payment_type' => $vp['amt'] > 50 ? 'monthly_subscription' : 'per_session',
                'reference_number' => $vp['ref'],
                'status' => 'verified',
                'verified_at' => Carbon::parse($vp['date']),
                'verified_by' => $ownerUser->id,
                'created_at' => Carbon::parse($vp['date']),
            ];
            if ($vp['sub']) {
                $pData['member_subscription_id'] = $createdSubscriptions[$vp['m']]->id;
            }

            $p = Payment::create($pData);
            $pRefs[$vp['code']] = $p;

            Revenue::create([
                'revenue_code' => 'REV-' . str_pad($revIndex++, 3, '0', STR_PAD_LEFT),
                'payment_id' => $p->id,
                'budget_id' => $budOps->id,
                'amount' => $vp['amt'],
                'revenue_date' => Carbon::parse($vp['date'])->toDateString()
            ]);
        }

        // PENDING PAYMENTS (Exact Match to Screenshot 5: 2 Pending Approvals)
        Payment::create([
            'payment_code' => 'REQ-001',
            'customer_id' => $createdMembers['ECO-002']->customer_id,
            'member_id' => $createdMembers['ECO-002']->id,
            'member_subscription_id' => $createdSubscriptions['ECO-002']->id,
            'payment_method_id' => $gcash->id,
            'amount' => 750.00,
            'payment_type' => 'monthly_subscription',
            'reference_number' => 'GCash-20260917-48291',
            'status' => 'pending',
            'created_at' => Carbon::parse('2026-09-17 10:42:00')
        ]);

        Payment::create([
            'payment_code' => 'REQ-002',
            'customer_id' => $createdMembers['ECO-005']->customer_id,
            'member_id' => $createdMembers['ECO-005']->id,
            'member_subscription_id' => $createdSubscriptions['ECO-005']->id,
            'payment_method_id' => $cash->id,
            'amount' => 750.00,
            'payment_type' => 'monthly_subscription',
            'status' => 'pending',
            'created_at' => Carbon::parse('2026-09-16 14:05:00')
        ]);

        // =========================================================================
        // 9. ATTENDANCE LOG SHEETS (Exact Match to Screenshots 1, 2 & 3)
        // =========================================================================
        $att1 = Attendance::create([
            'customer_id' => $createdMembers['ECO-001']->customer_id,
            'member_id' => $createdMembers['ECO-001']->id,
            'attendance_date' => Carbon::parse('2026-09-17'),
            'check_in_time' => '07:14:00',
            'check_out_time' => '08:45:00',
            'entry_type' => 'membership'
        ]);

        $att2 = Attendance::create([
            'customer_id' => $createdMembers['ECO-001']->customer_id,
            'member_id' => $createdMembers['ECO-001']->id,
            'attendance_date' => Carbon::parse('2026-09-15'),
            'check_in_time' => '06:45:00',
            'check_out_time' => '08:00:00',
            'entry_type' => 'membership'
        ]);

        Attendance::create([
            'customer_id' => $createdMembers['ECO-001']->customer_id,
            'member_id' => $createdMembers['ECO-001']->id,
            'attendance_date' => Carbon::parse('2026-09-12'),
            'check_in_time' => '07:02:00',
            'check_out_time' => '08:30:00',
            'entry_type' => 'membership'
        ]);

        Attendance::create([
            'customer_id' => $createdMembers['ECO-003']->customer_id,
            'member_id' => $createdMembers['ECO-003']->id,
            'payment_id' => $pRefs['PAY-009']->id,
            'attendance_date' => Carbon::parse('2026-09-15'),
            'check_in_time' => '09:20:00',
            'entry_type' => 'per_session'
        ]);

        Attendance::create([
            'customer_id' => $createdMembers['ECO-003']->customer_id,
            'member_id' => $createdMembers['ECO-003']->id,
            'payment_id' => $pRefs['PAY-008']->id,
            'attendance_date' => Carbon::parse('2026-09-14'),
            'check_in_time' => '10:05:00',
            'entry_type' => 'per_session'
        ]);

        // =========================================================================
        // 10. GYM NOTES (Screenshot 1: Workouts Logged = 4)
        // =========================================================================
        GymNote::create([
            'member_id' => $createdMembers['ECO-001']->id,
            'attendance_id' => $att1->id,
            'workout_date' => Carbon::parse('2026-09-17'),
            'routine_title' => 'Leg Day Heavy',
            'notes' => "Squats 5x5 @ 100kg\nLeg Press 4x12 @ 180kg\nWalking Lunges 3 sets",
        ]);

        GymNote::create([
            'member_id' => $createdMembers['ECO-001']->id,
            'attendance_id' => $att2->id,
            'workout_date' => Carbon::parse('2026-09-15'),
            'routine_title' => 'Push Workout',
            'notes' => "Barbell Bench 4x8 @ 70kg\nShoulder Overhead Press 3x10 @ 40kg\nDumbbell Lateral Raises 4x15 @ 10kg",
        ]);

        GymNote::create([
            'member_id' => $createdMembers['ECO-001']->id,
            'workout_date' => Carbon::parse('2026-09-12'),
            'routine_title' => 'Back & Biceps Pull Routine',
            'notes' => "Deadlifts 4x6 @ 120kg\nLat Pulldowns 4x10 @ 65kg\nBarbell Curls 3x12 @ 30kg",
        ]);

        GymNote::create([
            'member_id' => $createdMembers['ECO-001']->id,
            'workout_date' => Carbon::parse('2026-09-08'),
            'routine_title' => 'Cardio & Abs Conditioning',
            'notes' => "Treadmill 25 mins incline 6.0 @ 8km/h\nHanging Leg Raises 4x15\nPlank 3x60 secs",
        ]);

        // =========================================================================
        // 11. AUDIT LOGS (Exact Match to Screenshot 6)
        // =========================================================================
        $auditList = [
            ['code' => 'AUD-001', 'uid' => $ownerUser->id, 'action' => 'Membership Renewed', 'mid' => 'ECO-001', 'val' => '1 Month', 'by' => 'Owner', 'date' => '2026-08-18 09:00:00'],
            ['code' => 'AUD-002', 'uid' => $ownerUser->id, 'action' => 'Membership Activated', 'mid' => 'ECO-002', 'val' => '1 Month', 'by' => 'Owner', 'date' => '2026-07-10 10:30:00'],
            ['code' => 'AUD-003', 'uid' => $ownerUser->id, 'action' => 'Daily Access Granted', 'mid' => 'ECO-003', 'val' => '24 Hours', 'by' => 'Owner', 'date' => '2026-09-15 06:15:00'],
            ['code' => 'AUD-004', 'uid' => $ownerUser->id, 'action' => 'Membership Renewed', 'mid' => 'ECO-004', 'val' => '1 Month', 'by' => 'Owner', 'date' => '2026-09-04 08:00:00'],
            ['code' => 'AUD-005', 'uid' => null,           'action' => 'Membership Expired', 'mid' => 'ECO-002', 'val' => null,     'by' => 'System', 'date' => '2026-09-13 00:00:00'],
        ];

        foreach ($auditList as $al) {
            $aData = [
                'log_code' => $al['code'],
                'user_id' => $al['uid'],
                'action' => $al['action'],
                'performed_by' => $al['by'],
                'created_at' => Carbon::parse($al['date'])
            ];
            if (Schema::hasColumn('audit_logs', 'validity_period')) $aData['validity_period'] = $al['val'];
            if (Schema::hasColumn('audit_logs', 'validity')) $aData['validity'] = $al['val'];
            if (Schema::hasColumn('audit_logs', 'entity_type')) $aData['entity_type'] = Member::class;
            if (Schema::hasColumn('audit_logs', 'entity_id')) $aData['entity_id'] = $createdMembers[$al['mid']]->id;

            AuditLog::create($aData);
        }
    }
}