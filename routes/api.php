<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Course\CourseController;
use App\Http\Controllers\Invoice\InvoiceController;
use Illuminate\Support\Facades\Route;

// 登录
Route::post('/login', [AuthController::class, 'login']);

// 需要认证的路由
Route::middleware('auth:api')->group(function () {
    // 登出
    Route::post('/logout', [AuthController::class, 'logout']);

    // 课程管理
    Route::prefix('courses')->group(function () {
        Route::post('/', [CourseController::class, 'store']);
        Route::post('/{course}/attach-students', [CourseController::class, 'attachStudents']);
        Route::get('/my', [CourseController::class, 'my']);
    });

    // 账单相关路由
    Route::prefix('invoices')->group(function () {
        Route::post('/', [InvoiceController::class, 'store']);
        Route::post('/{invoice}/send', [InvoiceController::class, 'send']);
        Route::get('/my', [InvoiceController::class, 'my']);
    });
});
