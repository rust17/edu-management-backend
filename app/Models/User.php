<?php

namespace App\Models;

use App\Models\Course;
use App\Models\Invoice;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'role', 'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $dates = ['deleted_at'];

    public $avatar = '/vendor/laravel-admin/AdminLTE/dist/img/user2-160x160.jpg';

    const ROLE_ADMIN = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';

    // 获取该教师的所有课程
    public function teacherCourses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    // 获取该学生参加的所有课程
    public function studentCourses()
    {
        return $this->belongsToMany(Course::class, 'course_students', 'student_id', 'course_id');
    }

    // 获取该学生的所有发票
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'student_id');
    }

    /**
     * 获取教师扩展信息
     */
    public function teacherProfile()
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * 获取学生扩展信息
     */
    public function studentProfile()
    {
        return $this->hasOne(Student::class);
    }
}
