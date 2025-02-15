<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Course\StudentController as StudentCourseController;
use App\Http\Controllers\Course\TeacherController;
use App\Http\Controllers\Invoice\StudentController as StudentInvoiceController;
use App\Http\Controllers\Invoice\TeacherController as TeacherInvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

// Login
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:api')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Courses
    Route::prefix('courses')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // Create a course
            Route::post('/', [TeacherController::class, 'store']);
            // Update a course
            Route::put('/{course}', [TeacherController::class, 'update']);
            // Get teacher's course list
            Route::get('/teacher-courses', [TeacherController::class, 'teacherCourses']);
            // Get course details
            Route::get('/teacher-courses/{course}', [TeacherController::class, 'teacherCourse']);
        });

        Route::middleware('role:student')->group(function () {
            // View my courses
            Route::get('/student-courses', [StudentCourseController::class, 'studentCourses']);
            // View my course details
            Route::get('/student-courses/{course}', [StudentCourseController::class, 'studentCourse']);
        });
    });

    // Invoices
    Route::prefix('invoices')->group(function () {
        Route::middleware('role:teacher')->group(function () {
            // Create an invoice
            Route::post('/', [TeacherInvoiceController::class, 'store']);
            // Send an invoice
            Route::post('/{course}/send', [TeacherInvoiceController::class, 'send']);
            // Get teacher's invoice list
            Route::get('/teacher-invoices', [TeacherInvoiceController::class, 'teacherInvoices']);
        });

        Route::middleware('role:student')->group(function () {
            // Get student's invoice list
            Route::get('/student-invoices', [StudentInvoiceController::class, 'studentInvoices']);
            // Get student's invoice details
            Route::get('/student-invoices/{invoice}', [StudentInvoiceController::class, 'studentInvoice']);
        });
    });

    // Get teacher's student list
    Route::middleware('role:teacher')->get('/students', [StudentController::class, 'index']);

    // Get teacher's statistics
    Route::middleware('role:teacher')->get('/teacher-statistics', [StatisticsController::class, 'teacherStatistics']);

    // Get student's statistics
    Route::middleware('role:student')->get('/student-statistics', [StatisticsController::class, 'studentStatistics']);

    Route::prefix('payments')->group(function () {
        // Omise credit card payment
        Route::middleware('role:student')->post('omise-card', [PaymentController::class, 'omisePay']);
    });
});
