<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_id', 'student_id', 'payment_platform', 'payment_method',
        'transaction_no', 'transaction_fee', 'amount', 'status', 'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_PENDING = 'pending';

    const PAYMENT_PLATFORM_OMISE = 'omise';
    const PAYMENT_METHOD_CARD = 'card';

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
