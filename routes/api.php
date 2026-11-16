<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ServerClusterController;
use App\Http\Controllers\ApplicationController;
use App\Http\Middleware\AksesAuditor;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/public/pages/{slug}', [PageController::class, 'discovery']);

    // A4a & A4b: Server Cluster Catalog & Filter (Public)
    Route::get('/server_clusters', [ServerClusterController::class, 'index']);

    Route::get('/server_clusters', [ServerClusterController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/validation', [ValidationController::class, 'store']);
        Route::get('/validations', [ValidationController::class, 'show']);

        Route::middleware(AksesAuditor::class)->group(function () {
            Route::put('/validations/{id}/review', [ValidationController::class, 'review']);
        });

        Route::post('/pages', [PageController::class, 'store']);
        Route::post('/pages/{slug}/sections', [PageController::class, 'appendSection']);
        Route::put('/pages/{slug}/sections/{section_id}/fields', [PageController::class, 'updateFields']);
        Route::put('/pages/{slug}/publish', [PageController::class, 'publish']);

        Route::post('/applications', [ApplicationController::class, 'store']);
        Route::get('/applications', [ApplicationController::class, 'index']);
    });
});
