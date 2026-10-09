<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboard;
use App\Http\Controllers\Owner\QuickCheckInController;
use App\Http\Controllers\Owner\MemberManagementController;
use App\Http\Controllers\Owner\PaymentApprovalController;
use App\Http\Controllers\Owner\EquipmentController;
use App\Http\Controllers\Owner\FinancialController;
use App\Http\Controllers\Owner\PromoController;
use App\Http\Controllers\Member\MemberDashboardController;
use App\Http\Controllers\Member\MemberPaymentController;
use App\Http\Controllers\Member\GymNoteController;

Route::get('/', fn() => redirect()->route('login'));
// Profile Routes (Accessible to both Owner and Members)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
});
// Authentication
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Owner Portal
Route::middleware(['auth'])->prefix('owner')->name('owner.')->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('owner.checkin'))->name('dashboard');

    // Check-in & Dashboard
    Route::get('/checkin', [OwnerDashboard::class, 'index'])->name('checkin');
    Route::post('/quick-checkin/search', [QuickCheckInController::class, 'search'])->name('quickCheckIn.search');
    Route::post('/quick-checkin/per-session', [QuickCheckInController::class, 'confirmPerSession'])->name('quickCheckIn.perSession');
    Route::post('/quick-checkin/monthly', [QuickCheckInController::class, 'confirmMonthly'])->name('quickCheckIn.monthly');

    // Attendance Log Sheets
    Route::get('/attendance', [OwnerDashboard::class, 'attendanceLogs'])->name('attendance.index');

    // Member Management (Direct Enrollments & 1-Click Approvals)
    Route::get('/members', [MemberManagementController::class, 'index'])->name('members.index');
    Route::post('/members', [MemberManagementController::class, 'store'])->name('members.store');
    Route::post('/members/{member}/update', [MemberManagementController::class, 'update'])->name('members.update');
    Route::post('/members/{member}/approve', [MemberManagementController::class, 'approve'])->name('members.approve');

    // Promos & Rates Page
    Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
    Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
    Route::post('/promos/{package}/update', [PromoController::class, 'update'])->name('promos.update');
    Route::post('/promos/{package}/toggle', [PromoController::class, 'toggle'])->name('promos.toggle');
    Route::delete('/promos/{package}', [PromoController::class, 'destroy'])->name('promos.destroy');

    // Payments
    Route::get('/payments', [PaymentApprovalController::class, 'index'])->name('payments.index');
    Route::post('/payments/{payment}/verify', [PaymentApprovalController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [PaymentApprovalController::class, 'reject'])->name('payments.reject');
    Route::post('/payments/manual', [PaymentApprovalController::class, 'storeManual'])->name('payments.manual');

    // Audit Logs & Finance
    Route::get('/audit-logs', [OwnerDashboard::class, 'auditLogs'])->name('audit.index');
    Route::get('/finance', [FinancialController::class, 'index'])->name('finance.index');
    Route::post('/expenses', [FinancialController::class, 'storeExpense'])->name('expenses.store');

    // Equipment
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');
    Route::post('/equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    Route::post('/equipment/{equipment}/maintenance', [EquipmentController::class, 'storeMaintenance'])->name('equipment.maintenance');

    // Announcements
    Route::post('/announcements', [OwnerDashboard::class, 'storeAnnouncement'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [OwnerDashboard::class, 'destroyAnnouncement'])->name('announcements.destroy');
});

// Member Portal
Route::middleware(['auth'])->prefix('member')->name('member.')->group(function () {
    Route::get('/overview', [MemberDashboardController::class, 'index'])->name('overview');
    Route::post('/pay-subscription', [MemberPaymentController::class, 'submitPayment'])->name('paySubscription');
    Route::post('/redeem-points', [MemberDashboardController::class, 'redeemPoints'])->name('redeemPoints');
    Route::get('/gym-notes', [GymNoteController::class, 'index'])->name('notes.index');
    Route::post('/gym-notes', [GymNoteController::class, 'store'])->name('notes.store');
    Route::delete('/gym-notes/{gymNote}', [GymNoteController::class, 'destroy'])->name('notes.destroy');
});