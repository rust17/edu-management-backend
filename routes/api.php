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

    // 课程
    Route::prefix('courses')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // 创建课程
            Route::post('/', [CourseController::class, 'store']);
            // 关联学生到课程
            Route::post('/{course}/attach-students', [CourseController::class, 'attachStudents']);
        });

        Route::middleware('role:student')->group(function () {
            // 查看我的课程
            Route::get('/my', [CourseController::class, 'my']);
        });
    });

    // 账单
    Route::prefix('invoices')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // 创建账单
            Route::post('/', [InvoiceController::class, 'store']);
            // 发送账单
            Route::post('/{invoice}/send', [InvoiceController::class, 'send']);
        });

        Route::middleware('role:student')->group(function () {
            // 查看我的账单
            Route::get('/my', [InvoiceController::class, 'my']);
        });
    });
});
