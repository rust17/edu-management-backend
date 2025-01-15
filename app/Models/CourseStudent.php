<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id', 'student_id'
    ];

    public $timestamps = false;

    // 获取关联的课程
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // 获取关联的学生
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
