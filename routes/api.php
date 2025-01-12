<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Course\CourseController;
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
    });
});
