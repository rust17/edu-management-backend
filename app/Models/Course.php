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

    // Get the teacher of the course
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Get all students attending the course
    public function students()
    {
        return $this->belongsToMany(User::class, 'course_students', 'course_id', 'student_id');
    }

    // Get all invoices for the course
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
