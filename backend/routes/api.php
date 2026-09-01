<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\FieldImageController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\ReservationController;

/*
|--------------------------------------------------------------------------
| API Routes â€” myTerrain
|--------------------------------------------------------------------------
| PrÃ©fixe : /api/v1
| Middleware auth : sanctum
*/

Route::prefix('v1')->group(function () {

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // AUTHENTIFICATION (publique)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login',    [AuthController::class, 'login']);

        // Routes protÃ©gÃ©es
        Route::post('/payments/webhook', [\App\Http\Controllers\Api\PaymentController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout',   [AuthController::class, 'logout']);
            Route::get('/me',        [AuthController::class, 'me']);
            Route::put('/profile',   [AuthController::class, 'updateProfile']);
        });
    });

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ROUTES PUBLIQUES
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // (Phase 3 : Terrains)
    Route::get('/fields',               [FieldController::class, 'index']);
    Route::get('/fields/{field}',       [FieldController::class, 'show']);
    
    // (Phase 4 : CrÃ©neaux)
    Route::get('/fields/{field}/slots', [TimeSlotController::class, 'index']);
    
    // Route::get('/fields/{field}/reviews', [ReviewController::class, 'index']);
    // Route::get('/matches',              [MatchController::class, 'index']);

    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // ROUTES PROTÃ‰GÃ‰ES (auth requise)
    // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::post('/payments/webhook', [\App\Http\Controllers\Api\PaymentController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {

        // Profil
        // Route::put('/profile',          [ProfileController::class, 'update']);
        // Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

        // Terrains â€” Joueur
        // Route::post('/reviews',         [ReviewController::class, 'store']);
        // Route::post('/favorites',       [FavoriteController::class, 'store']);
        // Route::get('/favorites',        [FavoriteController::class, 'index']);
        // Route::delete('/favorites/{fieldId}', [FavoriteController::class, 'destroy']);

        // RÃ©servations â€” Joueur
        Route::get('/reservations',           [ReservationController::class, 'index']);
        Route::post('/reservations',          [ReservationController::class, 'store']);
        Route::get('/reservations/{id}',      [ReservationController::class, 'show']);
        Route::put('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);
        Route::post('/reservations/{reservation}/pay', [App\Http\Controllers\Api\PaymentController::class, 'pay']);

        // Paiements
        // Route::post('/payments',        [PaymentController::class, 'initiate']);
        // Route::get('/payments/{id}',    [PaymentController::class, 'show']);

        // Notifications
        Route::get('/notifications',    [App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::patch('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markRead']);
        Route::patch('/notifications/read-all',  [App\Http\Controllers\Api\NotificationController::class, 'markAllRead']);

        // Matchs
        // Route::post('/matches',         [MatchController::class, 'store']);
        // Route::post('/matches/{id}/join',  [MatchController::class, 'join']);
        // Route::delete('/matches/{id}/leave', [MatchController::class, 'leave']);

        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // LOUEUR (role: owner)
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::middleware('role:owner,admin')->group(function () {
            Route::get('/owner/fields',         [FieldController::class, 'myFields']);
            Route::post('/fields',              [FieldController::class, 'store']);
            Route::put('/fields/{field}',       [FieldController::class, 'update']);
            Route::delete('/fields/{field}',    [FieldController::class, 'destroy']);
            
            Route::post('/fields/{field}/images',   [FieldImageController::class, 'store']);
            Route::delete('/fields/{field}/images/{image}', [FieldImageController::class, 'destroy']);
            Route::patch('/fields/{field}/images/{image}/primary', [FieldImageController::class, 'setPrimary']);

            // CrÃ©neaux
            Route::post('/fields/{field}/slots',        [TimeSlotController::class, 'store']);
            Route::delete('/fields/{field}/slots/{slot}', [TimeSlotController::class, 'destroy']);
            // Route::put('/fields/{field}/slots/{slot}',  [TimeSlotController::class, 'update']);
            // Route::post('/fields/{field}/slots/bulk',   [TimeSlotController::class, 'bulkCreate']);
            
            // RÃ©servations Loueur
            Route::get('/owner/reservations',    [ReservationController::class, 'ownerIndex']);
            Route::patch('/reservations/{id}/confirm', [ReservationController::class, 'confirm']);
            
            // Route::get('/owner/dashboard',       [OwnerController::class, 'dashboard']);
            // Route::get('/owner/revenue',         [OwnerController::class, 'revenue']);
        });

        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        // ADMINISTRATEUR
        // â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::get('/stats', [App\Http\Controllers\Api\AdminController::class, 'getStats']);
            Route::get('/owners', [App\Http\Controllers\Api\AdminController::class, 'getOwners']);
            Route::post('/owners', [App\Http\Controllers\Api\AdminController::class, 'createOwner']);
            Route::put('/owners/{id}', [App\Http\Controllers\Api\AdminController::class, 'updateOwner']);
            Route::patch('/owners/{id}/toggle-status', [App\Http\Controllers\Api\AdminController::class, 'toggleOwnerStatus']);
            Route::delete('/owners/{id}', [App\Http\Controllers\Api\AdminController::class, 'deleteOwner']);
        });
    });

    // Webhook paiement (non authentifiÃ©, signature vÃ©rifiÃ©e en interne)
    // Route::post('/payments/webhook', [PaymentController::class, 'webhook']);
});

// TEMPORARY ROUTE FOR SEEDING
Route::get('/run-seeder', function () {
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    return response()->json(['message' => 'Database seeded successfully!', 'output' => \Illuminate\Support\Facades\Artisan::output()]);
});

