<?php

namespace App\Models;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'name', 'year_month', 'fee', 'teacher_id'
    ];

    protected $dates = [
        'year_month',
        'deleted_at'
    ];

    protected $casts = [
        'year_month' => 'date',
    ];

    // 获取课程的教师
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // 获取参加该课程的所有学生
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_students', 'course_id', 'student_id');
    }

    // 获取该课程的所有发票
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
