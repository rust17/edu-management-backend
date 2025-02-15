<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Student Model
 * Mainly stores student information, such as: grade, major. When resources need to frequently use specific fields of students, they can be associated with this table.
 */
class Student extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['user_id'];

    protected $dates = ['deleted_at'];

    /**
     * Get associated user information
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
