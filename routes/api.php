<?php

use App\Http\Controllers\Api\SocialPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('ai_posts')->group(function () {
    Route::get('/', [SocialPostController::class, 'index']);
    Route::post('/generate', [SocialPostController::class, 'generate']);
    Route::get('/{post}', [SocialPostController::class, 'show']);
    Route::post('/{post}/regenerate', [SocialPostController::class, 'regenerate']);
    Route::post('/{post}/approve', [SocialPostController::class, 'approve']);
});
