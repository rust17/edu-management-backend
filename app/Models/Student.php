<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 学生模型
 * 主要存放学生的信息，例如：年纪、专业。当资源需要频繁使用学生的特定字段时，可以与该表关联。
 */
class Student extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['user_id'];

    protected $dates = ['deleted_at'];

    /**
     * 获取关联的用户信息
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
