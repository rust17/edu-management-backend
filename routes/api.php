<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Course\StudentController as StudentCourseController;
use App\Http\Controllers\Course\TeacherController;
use App\Http\Controllers\Invoice\StudentController as StudentInvoiceController;
use App\Http\Controllers\Invoice\TeacherController as TeacherInvoiceController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StatisticsController;
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
            Route::post('/', [TeacherController::class, 'store']);
            // 编辑课程
            Route::put('/{course}', [TeacherController::class, 'update']);
            // 获取课程列表
            Route::get('/teacher-courses', [TeacherController::class, 'teacherCourses']);
            // 获取课程详情
            Route::get('/teacher-courses/{course}', [TeacherController::class, 'teacherCourse']);
        });

        Route::middleware('role:student')->group(function () {
            // 查看我的课程
            Route::get('/student-courses', [StudentCourseController::class, 'studentCourses']);
            // 查看我的课程详情
            Route::get('/student-courses/{course}', [StudentCourseController::class, 'studentCourse']);
        });
    });

    // 账单
    Route::prefix('invoices')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // 创建账单
            Route::post('/', [TeacherInvoiceController::class, 'store']);
            // 发送账单
            Route::post('/{course}/send', [TeacherInvoiceController::class, 'send']);
            // 获取老师账单列表
            Route::get('/teacher-invoices', [TeacherInvoiceController::class, 'teacherInvoices']);
        });

        Route::middleware('role:student')->group(function () {
            // 获取学生的账单列表
            Route::get('/student-invoices', [StudentInvoiceController::class, 'studentInvoices']);
            // 获取学生的账单详情
            Route::get('/student-invoices/{invoice}', [StudentInvoiceController::class, 'studentInvoice']);
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
