<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FirebaseService;

class SeedFirebase extends Command
{
    protected $signature = 'firebase:seed';
    protected $description = 'Upload all Eco Heights Gym seed data directly to Google Cloud Firestore';

    public function handle()
    {
        $this->info('Connecting to Google Firebase Cloud Firestore...');
        $fb = new FirebaseService();

        // 1. Seed Members to Firebase
        $this->info('Uploading members to Firebase...');
        $members = [
            ['member_code' => 'ECO-001', 'name' => 'Maria Santos', 'email' => 'maria@gmail.com', 'contact_number' => '09171234567', 'status' => 'active', 'reward_points' => 485, 'plan' => 'Monthly Standard Access'],
            ['member_code' => 'ECO-002', 'name' => 'Carlos Dela Cruz', 'email' => 'carlos@gmail.com', 'contact_number' => '09281234567', 'status' => 'expired', 'reward_points' => 30, 'plan' => 'Monthly Standard Access'],
            ['member_code' => 'ECO-003', 'name' => 'Liza Reyes', 'email' => 'liza@gmail.com', 'contact_number' => '09391234567', 'status' => 'active', 'reward_points' => 15, 'plan' => 'Daily Walk-In Session'],
            ['member_code' => 'ECO-004', 'name' => 'Ryan Aquino', 'email' => 'ryan@gmail.com', 'contact_number' => '09501234567', 'status' => 'active', 'reward_points' => 60, 'plan' => 'Monthly Standard Access'],
            ['member_code' => 'ECO-005', 'name' => 'Jenny Flores', 'email' => 'jenny@gmail.com', 'contact_number' => '09611234567', 'status' => 'pending', 'reward_points' => 0, 'plan' => 'Monthly Standard Access'],
        ];

        foreach ($members as $m) {
            $fb->setDocument('members', $m['member_code'], $m);
        }

        // 2. Seed Packages to Firebase
        $this->info('Uploading packages to Firebase...');
        $packages = [
            ['package_code' => 'PKG-MTH-750', 'name' => 'Monthly Standard Access', 'price' => 750.00, 'duration_in_days' => 30, 'plan_type' => 'monthly'],
            ['package_code' => 'PKG-QTR-2100', 'name' => 'Quarterly Pass (3 Months)', 'price' => 2100.00, 'duration_in_days' => 90, 'plan_type' => 'quarterly'],
            ['package_code' => 'PKG-YRL-7500', 'name' => 'Yearly VIP Pass (12 Months)', 'price' => 7500.00, 'duration_in_days' => 365, 'plan_type' => 'yearly'],
            ['package_code' => 'PKG-DAY-050', 'name' => 'Daily Walk-In Session', 'price' => 50.00, 'duration_in_days' => 1, 'plan_type' => 'daily'],
        ];
        foreach ($packages as $pkg) {
            $fb->setDocument('packages', $pkg['package_code'], $pkg);
        }

        // 3. Seed Equipment to Firebase
        $this->info('Uploading gym equipment to Firebase...');
        $equipment = [
            ['code' => 'EQP-001', 'name' => 'Olympic Barbell (20kg)', 'category' => 'Free Weights', 'quantity' => 4, 'location' => 'Free Weights Area', 'status' => 'operational'],
            ['code' => 'EQP-002', 'name' => 'Dumbbell Set (5–50kg)', 'category' => 'Free Weights', 'quantity' => 1, 'location' => 'Free Weights Area', 'status' => 'operational'],
            ['code' => 'EQP-003', 'name' => 'Squat Rack', 'category' => 'Racks & Frames', 'quantity' => 2, 'location' => 'Lifting Platform', 'status' => 'operational'],
            ['code' => 'EQP-004', 'name' => 'Treadmill (Commercial)', 'category' => 'Cardio', 'quantity' => 3, 'location' => 'Cardio Zone', 'status' => 'needs_maintenance'],
            ['code' => 'EQP-005', 'name' => 'Stationary Bike', 'category' => 'Cardio', 'quantity' => 2, 'location' => 'Cardio Zone', 'status' => 'operational'],
            ['code' => 'EQP-006', 'name' => 'Cable Machine (Dual Stack)', 'category' => 'Machines', 'quantity' => 1, 'location' => 'Machine Area', 'status' => 'under_repair'],
        ];
        foreach ($equipment as $eq) {
            $fb->setDocument('equipment', $eq['code'], $eq);
        }

        // 4. Seed Announcements to Firebase
        $this->info('Uploading announcements to Firebase...');
        $announcements = [
            ['title' => 'Extended Gym Hours Starting October', 'badge' => 'SCHEDULE', 'message' => 'We are extending operating hours from 6:00 AM to 11:00 PM on weekdays.'],
            ['title' => 'New Olympic Barbells & Bumper Plates Available', 'badge' => 'PROMO', 'message' => 'Brand new rogue equipment added to the lifting platform.'],
            ['title' => 'Closed on Sunday Evening for Deep Sanitization', 'badge' => 'INFO', 'message' => 'Our gym floor will undergo scheduled sanitization at 8:00 PM.'],
        ];
        foreach ($announcements as $anc) {
            $fb->addDocument('announcements', $anc);
        }

        $this->info('All gym data successfully uploaded to Google Cloud Firestore (Firebase)!');
    }
}