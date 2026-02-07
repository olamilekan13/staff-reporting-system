<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\DepartmentController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProposalController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group with "/api" prefix.
|
*/

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.')->group(function () {

    // Health check (public)
    Route::get('/health', HealthController::class);

    // Auth routes (rate limited for security)
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/verify-kingschat', [AuthController::class, 'verifyKingschat']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes (require authentication)
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Auth routes requiring authentication
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
        });

        // Reports
        Route::apiResource('reports', ReportController::class);
        Route::post('/reports/{report}/submit', [ReportController::class, 'submit']);
        Route::post('/reports/{report}/review', [ReportController::class, 'review']);
        Route::get('/reports/{report}/download', [ReportController::class, 'download']);

        // Comments on Reports
        Route::get('/reports/{report}/comments', [CommentController::class, 'indexForReport']);
        Route::post('/reports/{report}/comments', [CommentController::class, 'storeForReport']);

        // Proposals
        Route::apiResource('proposals', ProposalController::class);
        Route::post('/proposals/{proposal}/review', [ProposalController::class, 'review']);
        Route::get('/proposals/{proposal}/download', [ProposalController::class, 'download']);

        // Comments on Proposals
        Route::get('/proposals/{proposal}/comments', [CommentController::class, 'indexForProposal']);
        Route::post('/proposals/{proposal}/comments', [CommentController::class, 'storeForProposal']);

        // Comment management (update/delete)
        Route::put('/comments/{comment}', [CommentController::class, 'update']);
        Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

        // Announcements
        Route::apiResource('announcements', AnnouncementController::class);
        Route::post('/announcements/{announcement}/read', [AnnouncementController::class, 'markAsRead']);

        // Users (Admin) - import routes before apiResource to avoid {user} conflict
        Route::get('/users/import/template', [UserController::class, 'importTemplate']);
        Route::post('/users/import', [UserController::class, 'import']);
        Route::post('/users/{user}/activate', [UserController::class, 'activate']);
        Route::apiResource('users', UserController::class);

        // Departments
        Route::get('/departments/{department}/staff', [DepartmentController::class, 'staff']);
        Route::post('/departments/{department}/staff', [DepartmentController::class, 'assignStaff']);
        Route::apiResource('departments', DepartmentController::class);

        // Settings (Admin) - logo/favicon routes before {group} to avoid conflict
        Route::post('/settings/logo', [SettingController::class, 'uploadLogo']);
        Route::post('/settings/favicon', [SettingController::class, 'uploadFavicon']);
        Route::get('/settings/{group}', [SettingController::class, 'show']);
        Route::get('/settings', [SettingController::class, 'index']);
        Route::put('/settings', [SettingController::class, 'update']);

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::post('/{notification}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{notification}', [NotificationController::class, 'destroy']);
        });
    });
});
