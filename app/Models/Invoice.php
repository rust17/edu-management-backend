<?php

namespace App\Models;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Invoice extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'no', 'course_id', 'student_id', 'status', 'amount', 'sent_at'
    ];

    protected $dates = ['deleted_at', 'sent_at'];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';

    public static function bootInvoice()
    {
        static::creating(function ($model) {
            $model->no = static::generateNo();
        });
    }

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

    // 获取关联的支付
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * 生成发票编号
     *
     * @return string
     */
    public static function generateNo()
    {
        // 使用微秒时间戳作为前缀
        $prefix = date('YmdHis') . sprintf('%03d', microtime(true) * 1000 % 1000);
        // 添加 6 位随机数
        $suffix = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        return $prefix . $suffix;
    }
}
