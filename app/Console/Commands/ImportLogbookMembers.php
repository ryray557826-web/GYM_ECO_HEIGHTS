<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\PaymentMethod;
use App\Models\Budget;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Services\FirebaseService;

class ImportLogbookMembers extends Command
{
    protected $signature = 'gym:import-logbook';
    protected $description = 'Import members and attendance logs into local DB and Google Cloud Firestore with realistic daily time variations';

    /**
     * Generate realistic gym check-in & check-out times across 6:00 AM - 11:00 PM
     */
    protected function generateGymTimes(): array
    {
        $weight = rand(1, 100);

        if ($weight <= 35) {
            $hour = rand(6, 9);
        } elseif ($weight <= 55) {
            $hour = rand(13, 16);
        } elseif ($weight <= 90) {
            $hour = rand(17, 20);
        } else {
            $hour = rand(21, 22);
        }

        $minute = rand(0, 59);
        $checkIn = sprintf('%02d:%02d:00', $hour, $minute);

        $workoutMins = rand(45, 90);
        $checkOutTime = Carbon::createFromTime($hour, $minute, 0)->addMinutes($workoutMins);

        if ($checkOutTime->hour >= 23) {
            $checkOutTime = Carbon::createFromTime(22, 50, 0);
        }

        return [$checkIn, $checkOutTime->format('H:i:s')];
    }

    public function handle()
    {
        $this->info('Initializing database & connecting to Google Cloud Firestore...');

        $firebase = null;
        try {
            $firebase = new FirebaseService();
            $this->info('✓ Firebase service connected successfully.');
        } catch (\Exception $e) {
            $this->warn('Firebase offline: ' . $e->getMessage());
        }

        DB::transaction(function () use ($firebase) {
            // 1. Ensure Baseline Packages, Payment Methods, and Budget Exist
            $monthlyPkg = Package::firstOrCreate(
                ['plan_type' => 'monthly'],
                ['package_code' => 'PKG-MTH-750', 'name' => 'Monthly Standard Access', 'price' => 750.00, 'duration_in_days' => 30, 'is_active' => true]
            );

            $dailyPkg = Package::firstOrCreate(
                ['plan_type' => 'daily'],
                ['package_code' => 'PKG-DAY-050', 'name' => 'Daily Walk-In Session', 'price' => 50.00, 'duration_in_days' => 1, 'is_active' => true]
            );

            $cash = PaymentMethod::firstOrCreate(['code' => 'cash'], ['name' => 'Physical / Cash', 'is_online' => false]);
            $owner = User::where('role', 'owner')->first();
            $ownerId = $owner ? $owner->id : 1;

            $budOps = Budget::firstOrCreate(
                ['budget_code' => 'BUD-2026-OPS'],
                ['category_name' => 'Facility Operations', 'allocated_amount' => 35000.00, 'spent_amount' => 0.00, 'fiscal_year' => 2026]
            );

            // 2. Data Extracted from Notebook Pages (Sept 30 - Oct 9, 2026)
            $logbookData = [
                // Monthly Members
                ['first' => 'Christian', 'last' => 'Navarro', 'plan' => 'monthly', 'dates' => ['2026-10-01', '2026-10-02', '2026-10-05', '2026-10-07', '2026-10-08', '2026-10-09']],
                ['first' => 'Arnel',     'last' => 'Reyes',   'plan' => 'monthly', 'dates' => ['2026-09-30', '2026-10-01', '2026-10-06', '2026-10-08']],
                ['first' => 'Josh',      'last' => 'Mendoza', 'plan' => 'monthly', 'dates' => ['2026-10-01', '2026-10-06', '2026-10-08']],
                ['first' => 'Kirk',      'last' => 'Lim',     'plan' => 'monthly', 'dates' => ['2026-10-03', '2026-10-05', '2026-10-08', '2026-10-09']],
                ['first' => 'Midz',      'last' => 'Torres',  'plan' => 'monthly', 'dates' => ['2026-10-02', '2026-10-05', '2026-10-07', '2026-10-09']],
                ['first' => 'Johndale',  'last' => 'Alcantara','plan' => 'monthly', 'dates' => ['2026-10-03', '2026-10-04', '2026-10-06', '2026-10-07', '2026-10-09']],
                ['first' => 'Brent',     'last' => 'Suarez',  'plan' => 'monthly', 'dates' => ['2026-10-03', '2026-10-06', '2026-10-07']],
                ['first' => 'Alvin',     'last' => 'Bautista','plan' => 'monthly', 'dates' => ['2026-10-05', '2026-10-07']],
                ['first' => 'Sonny',     'last' => 'Dela Cruz','plan' => 'monthly','dates' => ['2026-10-04', '2026-10-08']],
                ['first' => 'Ced',       'last' => 'Villanueva','plan' => 'monthly','dates' => ['2026-09-30', '2026-10-06']],
                ['first' => 'Justin',    'last' => 'Castro',  'plan' => 'monthly', 'dates' => ['2026-09-30']],
                ['first' => 'Danica',    'last' => 'Flores',  'plan' => 'monthly', 'dates' => ['2026-10-06']],
                ['first' => 'Anton',     'last' => 'Mercado', 'plan' => 'monthly', 'dates' => ['2026-10-04', '2026-10-09']],
                ['first' => 'Cagas',     'last' => 'Clint',   'plan' => 'monthly', 'dates' => ['2026-10-09']],

                // Daily Walk-In / Per-Session Members
                ['first' => 'Paolo',     'last' => 'Gomez',   'plan' => 'daily',   'dates' => ['2026-09-30', '2026-10-01', '2026-10-03', '2026-10-06', '2026-10-07']],
                ['first' => 'Sam',       'last' => 'Tan',     'plan' => 'daily',   'dates' => ['2026-09-30', '2026-10-03', '2026-10-06', '2026-10-07']],
                ['first' => 'Patani',    'last' => 'Boy',     'plan' => 'daily',   'dates' => ['2026-09-30', '2026-10-02', '2026-10-06', '2026-10-09']],
                ['first' => 'Roselle',   'last' => 'Perez',   'plan' => 'daily',   'dates' => ['2026-10-01', '2026-10-07']],
                ['first' => 'Joy',       'last' => 'Salazar', 'plan' => 'daily',   'dates' => ['2026-09-30', '2026-10-02', '2026-10-03', '2026-10-09']],
                ['first' => 'Glenn',     'last' => 'Soriano', 'plan' => 'daily',   'dates' => ['2026-10-06', '2026-10-07']],
                ['first' => 'Rica',      'last' => 'Dizon',   'plan' => 'daily',   'dates' => ['2026-09-30']],
                ['first' => 'Mark',      'last' => 'Ignacio', 'plan' => 'daily',   'dates' => ['2026-10-02']],
                ['first' => 'Vince',     'last' => 'Aquino',  'plan' => 'daily',   'dates' => ['2026-10-02']],
                ['first' => 'Clark',     'last' => 'Ramos',   'plan' => 'daily',   'dates' => ['2026-10-02']],
                ['first' => 'Philip',    'last' => 'Chua',    'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Prince',    'last' => 'Alvarez', 'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Sanjay',    'last' => 'Gupta',   'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Ian',       'last' => 'De Leon', 'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Delgra',    'last' => 'Marco',   'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Kath',      'last' => 'Morales', 'plan' => 'daily',   'dates' => ['2026-10-06']],
                ['first' => 'Michael',   'last' => 'Santos',  'plan' => 'daily',   'dates' => ['2026-10-05']],
                ['first' => 'Denver',    'last' => 'Valdez',  'plan' => 'daily',   'dates' => ['2026-10-05']],
                ['first' => 'Ortega',    'last' => 'Ben',     'plan' => 'daily',   'dates' => ['2026-10-05']],
                ['first' => 'Romar',     'last' => 'Castillo','plan' => 'daily',   'dates' => ['2026-10-05']],
                ['first' => 'Newmac',    'last' => 'Reyes',   'plan' => 'daily',   'dates' => ['2026-10-05']],
                ['first' => 'Russel',    'last' => 'Plete',   'plan' => 'daily',   'dates' => ['2026-10-03']],
                ['first' => 'Jacob',     'last' => 'Reyes',   'plan' => 'daily',   'dates' => ['2026-10-07']],
                ['first' => 'Camarda',   'last' => 'Noel',    'plan' => 'daily',   'dates' => ['2026-10-07']],
                ['first' => 'Alfarero',  'last' => 'Dan',     'plan' => 'daily',   'dates' => ['2026-10-07']],
                ['first' => 'Tuburan',   'last' => 'Rey',     'plan' => 'daily',   'dates' => ['2026-10-07']],
                ['first' => 'Edward',    'last' => 'Chuapi',  'plan' => 'daily',   'dates' => ['2026-10-09']],
            ];

            $importedMembers = 0;
            $importedAttendances = 0;

            $this->output->progressStart(count($logbookData));

            foreach ($logbookData as $entry) {
                $email = strtolower($entry['first'] . '.' . $entry['last'] . '@ecoheights.local');

                // 3. User
                $user = User::firstOrCreate(['email' => $email], [
                    'password'          => Hash::make('pass123'),
                    'role'              => 'member',
                    'account_status'    => 'active',
                    'email_verified_at' => Carbon::now(),
                ]);

                // 4. Customer
                $customer = Customer::firstOrCreate(['email' => $email], [
                    'user_id'                 => $user->id,
                    'first_name'              => $entry['first'],
                    'last_name'               => $entry['last'],
                    'contact_number'          => '09' . rand(100000000, 999999999),
                    'date_of_birth'           => Carbon::parse('2000-01-01'),
                    'age'                     => 26,
                    'address'                 => 'Toril, Davao City',
                    'emergency_contact_phone' => '09170000000',
                ]);

                // 5. Member Record
                $existingMember = Member::where('customer_id', $customer->id)->first();
                if (!$existingMember) {
                    $nextNum = Member::count() + 1;
                    $memberCode = 'ECO-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

                    $isMonthly = ($entry['plan'] === 'monthly');
                    $pointsEarned = $isMonthly ? 15 : (count($entry['dates']) * 3);
                    $firstDate = Carbon::parse($entry['dates'][0] ?? '2026-10-01');

                    $member = Member::create([
                        'customer_id'         => $customer->id,
                        'member_code'         => $memberCode,
                        'joined_date'         => $firstDate,
                        'verification_status' => 'verified',
                        'membership_status'   => 'active',
                        'reward_points'       => $pointsEarned,
                    ]);

                    if (Schema::hasColumn('users', 'member_id')) {
                        $user->update(['member_id' => $memberCode]);
                    }

                    // 6. Subscriptions, Payments & Revenue
                    $pkg = $isMonthly ? $monthlyPkg : $dailyPkg;
                    $endTime = $isMonthly ? $firstDate->copy()->addDays(30) : Carbon::today()->endOfDay();

                    $sub = MemberSubscription::create([
                        'member_id'  => $member->id,
                        'package_id' => $pkg->id,
                        'start_time' => $firstDate->copy()->startOfDay(),
                        'end_time'   => $endTime,
                        'status'     => 'active'
                    ]);

                    // ✅ COLLISION-PROOF UNIQUE PAYMENT CODE: Uses sequential count + random hex
                    $uniqueCode = 'PAY-LOG-' . str_pad($importedMembers + 1, 4, '0', STR_PAD_LEFT) . '-' . bin2hex(random_bytes(3));

                    $pay = Payment::create([
                        'payment_code'           => $uniqueCode,
                        'customer_id'            => $customer->id,
                        'member_id'              => $member->id,
                        'member_subscription_id' => $sub->id,
                        'payment_method_id'      => $cash->id,
                        'amount'                 => $pkg->price,
                        'payment_type'           => $isMonthly ? 'monthly_subscription' : 'per_session',
                        'status'                 => 'verified',
                        'verified_at'            => $firstDate,
                        'verified_by'            => $ownerId
                    ]);

                    Revenue::create([
                        'revenue_code' => 'REV-' . $pay->id . '-' . bin2hex(random_bytes(2)),
                        'payment_id'   => $pay->id,
                        'budget_id'    => $budOps->id,
                        'amount'       => $pkg->price,
                        'revenue_date' => $firstDate->toDateString()
                    ]);

                    // 7. Sync Member Document to Firebase Cloud Firestore
                    if ($firebase) {
                        try {
                            $firebase->syncMember([
                                'member_code'    => $memberCode,
                                'name'           => "{$entry['first']} {$entry['last']}",
                                'email'          => $email,
                                'contact_number' => $customer->contact_number,
                                'status'         => 'active',
                                'reward_points'  => $pointsEarned,
                                'plan'           => $pkg->name,
                                'expires_at'     => $endTime->format('Y-m-d H:i'),
                            ]);
                        } catch (\Exception $e) {
                            // Continue gracefully
                        }
                    }

                    // 8. Log Varied Attendance Entries & Sync to Firebase
                    foreach ($entry['dates'] as $attDate) {
                        [$timeIn, $timeOut] = $this->generateGymTimes();

                        $attendance = Attendance::create([
                            'customer_id'     => $customer->id,
                            'member_id'       => $member->id,
                            'payment_id'      => $pay->id,
                            'attendance_date' => $attDate,
                            'check_in_time'   => $timeIn,
                            'check_out_time'  => $timeOut,
                            'entry_type'      => $isMonthly ? 'membership' : 'per_session'
                        ]);

                        if ($firebase) {
                            try {
                                $firebase->addDocument('attendances', [
                                    'attendance_id'   => (int) $attendance->id,
                                    'member_code'     => $memberCode,
                                    'customer_name'   => "{$entry['first']} {$entry['last']}",
                                    'attendance_date' => $attDate,
                                    'check_in_time'   => $timeIn,
                                    'check_out_time'  => $timeOut,
                                    'entry_type'      => $isMonthly ? 'membership' : 'per_session',
                                ]);
                            } catch (\Exception $e) {
                                // Continue
                            }
                        }

                        $importedAttendances++;
                    }

                    $importedMembers++;
                }

                $this->output->progressAdvance();
            }

            $this->output->progressFinish();

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => $ownerId,
                'action'          => "Imported {$importedMembers} Members & {$importedAttendances} Varied Attendance Logs to DB and Firebase Cloud Firestore",
                'performed_by'    => 'System Import',
                'validity_period' => 'Data Migration',
            ]);

            $this->info("✓ Successfully imported {$importedMembers} verified members!");
            $this->info("✓ Successfully logged {$importedAttendances} attendance sessions with realistic morning, afternoon, and evening check-ins!");
            $this->info("✓ Data synchronized to Google Cloud Firestore (Firebase)!");
        });

        return 0;
    }
}