<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Course\CourseController;
use App\Http\Controllers\Invoice\InvoiceController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Student\StudentController;
use App\Http\Controllers\Statistics\StatisticsController;
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
            // 编辑课程
            Route::put('/{course}', [CourseController::class, 'update']);
            // 获取课程列表
            Route::get('/teacher-courses', [CourseController::class, 'teacherCourses']);
            // 获取课程详情
            Route::get('/teacher-courses/{course}', [CourseController::class, 'teacherCourse']);
        });

        Route::middleware('role:student')->group(function () {
            // 查看我的课程
            Route::get('/student-courses', [CourseController::class, 'studentCourses']);
            // 查看我的课程详情
            Route::get('/student-courses/{course}', [CourseController::class, 'studentCourse']);
        });
    });

    // 账单
    Route::prefix('invoices')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // 创建账单
            Route::post('/', [InvoiceController::class, 'store']);
            // 发送账单
            Route::post('/{course}/send', [InvoiceController::class, 'send']);
            // 获取老师账单列表
            Route::get('/teacher-invoices', [InvoiceController::class, 'teacherInvoices']);
        });

        Route::middleware('role:student')->group(function () {
            // 获取学生的账单列表
            Route::get('/student-invoices', [InvoiceController::class, 'studentInvoices']);
            // 获取学生的账单详情
            Route::get('/student-invoices/{invoice}', [InvoiceController::class, 'studentInvoice']);
        });
    });

    // 获取老师的学生列表
    Route::middleware('role:teacher')->get('/students', [StudentController::class, 'index']);

    // 获取老师的统计信息
    Route::middleware('role:teacher')->get('/teacher-statistics', [StatisticsController::class, 'teacherStatistics']);

    // 获取学生的统计信息
    Route::middleware('role:student')->get('/student-statistics', [StatisticsController::class, 'studentStatistics']);

    Route::prefix('payments')->group(function () {
        // omise 信用卡支付
        Route::middleware('role:student')->post('omise-card', [PaymentController::class, 'omisePay']);
    });
});
