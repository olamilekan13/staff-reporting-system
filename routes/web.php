<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\Admin;
use App\Http\Controllers\Web\AnnouncementController;
use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\Hod;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\Staff;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ─── Authentication ──────────────────────────────────────────────────
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login/verify', [LoginController::class, 'verifyKingsChatId'])->name('login.verify');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('logout', [LoginController::class, 'logout'])->middleware('auth'); // Fallback for GET requests

// ─── Admin Routes ────────────────────────────────────────────────────
Route::prefix('admin')
    ->middleware(['auth', 'role:super_admin,admin,head_of_operations'])
    ->group(function () {
        Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

        // Routes accessible to all admin roles (including Head of Operations)
        Route::resource('departments', Admin\DepartmentController::class)->names('admin.departments');
        Route::post('reports/{report}/review', [Admin\ReportController::class, 'review'])->name('admin.reports.review');
        Route::resource('reports', Admin\ReportController::class)->names('admin.reports');
        Route::resource('announcements', Admin\AnnouncementController::class)->names('admin.announcements');

        // Routes restricted to super_admin and admin only (NOT Head of Operations)
        Route::middleware('role:super_admin,admin')->group(function () {
            // User management routes
            Route::get('users/import/template', [Admin\UserController::class, 'importTemplate'])->name('admin.users.import.template');
            Route::post('users/import', [Admin\UserController::class, 'import'])->name('admin.users.import');
            Route::post('users/import/preview', [Admin\UserController::class, 'importPreview'])->name('admin.users.import.preview');
            Route::get('users/export', [Admin\UserController::class, 'export'])->name('admin.users.export');
            Route::post('users/{user}/toggle-activation', [Admin\UserController::class, 'toggleActivation'])->name('admin.users.toggle-activation');
            Route::resource('users', Admin\UserController::class)->names('admin.users');

            // Proposal routes
            Route::get('proposals', [Admin\ProposalController::class, 'index'])->name('admin.proposals.index');
            Route::get('proposals/{proposal}', [Admin\ProposalController::class, 'show'])->name('admin.proposals.show');
            Route::post('proposals/{proposal}/review', [Admin\ProposalController::class, 'review'])->name('admin.proposals.review');

            // Settings routes
            Route::get('settings', [Admin\SettingController::class, 'index'])->name('admin.settings.index');
            Route::put('settings', [Admin\SettingController::class, 'update'])->name('admin.settings.update');
            Route::post('settings/test-email', [Admin\SettingController::class, 'testEmail'])->name('admin.settings.test-email');

            // Activity logs
            Route::get('activity-logs', [Admin\ActivityLogController::class, 'index'])->name('admin.activity-logs.index');
        });
    });

// ─── HOD Routes ──────────────────────────────────────────────────────
Route::prefix('hod')
    ->middleware(['auth', 'role:hod'])
    ->group(function () {
        Route::get('dashboard', [Hod\DashboardController::class, 'index'])->name('hod.dashboard');
        Route::get('department', [Hod\DepartmentController::class, 'show'])->name('hod.department.show');
        Route::post('reports/{report}/review', [Hod\ReportController::class, 'review'])->name('hod.reports.review');
        Route::resource('reports', Hod\ReportController::class)->names('hod.reports');
    });

// ─── Staff Routes ────────────────────────────────────────────────────
Route::prefix('staff')
    ->middleware(['auth', 'role:staff'])
    ->group(function () {
        Route::get('dashboard', [Staff\DashboardController::class, 'index'])->name('staff.dashboard');
        Route::resource('reports', Staff\ReportController::class)->names('staff.reports');
        Route::resource('proposals', Staff\ProposalController::class)->names('staff.proposals');
    });

// ─── Common Authenticated Routes ────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/{notification}/view', [NotificationController::class, 'view'])->name('notifications.view');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Comments (AJAX)
    Route::post('reports/{report}/comments', [CommentController::class, 'storeForReport'])->name('reports.comments.store');
    Route::post('proposals/{proposal}/comments', [CommentController::class, 'storeForProposal'])->name('proposals.comments.store');

    // User search (for KingsChat share modal)
    Route::get('users/search', \App\Http\Controllers\Web\UserSearchController::class)->name('users.search');
});

// ─── Signed Media URL (for Office Online viewer) ────────────────────
Route::get('media/{media}/serve', function (\Spatie\MediaLibrary\MediaCollections\Models\Media $media) {
    return response()->file($media->getPath(), [
        'Content-Type' => $media->mime_type,
        'Content-Disposition' => 'inline; filename="' . $media->file_name . '"',
    ]);
})->name('media.serve')->middleware('signed');
