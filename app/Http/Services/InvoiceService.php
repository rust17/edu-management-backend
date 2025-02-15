<?php

namespace App\Http\Services;

use App\Models\Invoice;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvoiceService
{
    /**
     * Create invoices
     *
     * @param Course $course
     * @param array $studentIds
     * @return array
     * @throws Throwable
     */
    public function create(Course $course, array $studentIds): array
    {
        try {
            DB::beginTransaction();

            $invoices = [];
            foreach ($studentIds as $studentId) {
                // Check if the invoice already exists
                if (Invoice::where('course_id', $course->id)
                    ->where('student_id', $studentId)
                    ->exists()
                ) {
                    continue;
                }

                $invoices[] = Invoice::create([
                    'course_id' => $course->id,
                    'student_id' => $studentId,
                    'amount' => $course->fee,
                    'year_month' => $course->year_month,
                    'no' => Invoice::generateNo()
                ]);
            }

            DB::commit();
            return $invoices;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Send invoice
     *
     * @param Course $course
     * @param array $studentIds
     * @return void
     */
    public function send(Course $course, array $studentIds): void
    {
        try {
            DB::beginTransaction();

            $now = now();
            $course->invoices->whereIn('student_id', $studentIds)->map(
                fn (Invoice $invoice) => $invoice->update(['sent_at' => $now])
            );

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get the query builder for the teacher invoice list
     *
     * @param int $teacherId
     * @param array $filters
     * @return Builder
     */
    public function getTeacherInvoicesQuery(int $teacherId, array $filters): Builder
    {
        $query = Invoice::with(['student', 'course', 'payment'])
            ->whereHas('course', fn ($query) => $query->where('teacher_id', $teacherId))
            ->latest('id');

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by course keyword
        if (isset($filters['keyword'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            )->orWhereHas('student',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            );
        }

        // Filter by course year and month
        if (isset($filters['year_month'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth())
            );
        }

        // Filter by invoice sending time
        if (isset($filters['send_start']) && isset($filters['send_end'])) {
            $query->whereBetween('sent_at', [$filters['send_start'], $filters['send_end']]);
        }

        return $query->latest('id');
    }

    /**
     * Get the query builder for the student invoice list
     *
     * @param int $studentId
     * @param array $filters
     * @return Builder
     */
    public function getStudentInvoicesQuery(int $studentId, array $filters): Builder
    {
        $query = Invoice::where('student_id', $studentId)
            ->whereNotNull('sent_at') // After the teacher sends the invoice, the student can see it
            ->with(['course', 'payment'])
            ->latest('id');

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by course keyword
        if (isset($filters['keyword'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('name', 'like', '%' . $filters['keyword'] . '%')
            );
        }

        // Filter by course year and month
        if (isset($filters['year_month'])) {
            $query->whereHas('course',
                fn ($query) => $query->where('year_month', Carbon::parse($filters['year_month'])->startOfMonth())
            );
        }

        // Filter by invoice sending time
        if (isset($filters['send_start']) && isset($filters['send_end'])) {
            $query->whereBetween('sent_at', [$filters['send_start'], $filters['send_end']]);
        }

        return $query->latest('id');
    }
}
