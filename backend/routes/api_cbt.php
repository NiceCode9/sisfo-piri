<?php

use App\Http\Controllers\Api\Cbt\AnswerController;
use App\Http\Controllers\Api\Cbt\AuthController;
use App\Http\Controllers\Api\Cbt\ExamSessionController;
use App\Http\Controllers\Api\Cbt\QuestionController;
use App\Http\Controllers\Api\Cbt\ViolationController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/exam/active', [ExamSessionController::class, 'active']);
    Route::post('/exam/join', [ExamSessionController::class, 'join']);
    Route::get('/exam/questions', [QuestionController::class, 'index']);
    Route::post('/exam/answer', [AnswerController::class, 'store']);
    Route::post('/exam/heartbeat', [ExamSessionController::class, 'heartbeat'])->middleware('throttle:heartbeat');
    Route::post('/exam/violation', [ViolationController::class, 'store']);
    Route::post('/exam/finish', [ExamSessionController::class, 'finish']);
});

/*
|--------------------------------------------------------------------------
| Cara daftar (Laravel 11 — bootstrap/app.php)
|--------------------------------------------------------------------------
|
| ->withRouting(
|     // ...routes lain
|     then: function () {
|         Route::middleware('api')
|             ->prefix('api/cbt')
|             ->group(base_path('routes/api_cbt.php'));
|     },
| )
|
| Laravel <11 (RouteServiceProvider): daftarkan group serupa di method boot()/map().
|
| Tambahkan juga rate limiter di app/Providers/AppServiceProvider.php (boot()):
|
| RateLimiter::for('heartbeat', function (Request $request) {
|     return Limit::perMinute(6)->by($request->user()->id);
| });
*/
