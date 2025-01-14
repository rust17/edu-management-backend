<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        return [
            'invoice_id' => Invoice::factory(),
            'student_id' => User::factory(),
            'payment_platform' => Payment::PAYMENT_PLATFORM_OMISE,
            'payment_method' => Payment::PAYMENT_METHOD_CARD,
            'transaction_no' => $this->faker->unique()->numerify('##########'),
            'transaction_fee' => $this->faker->randomFloat(2, 0, 100),
            'amount' => $this->faker->randomFloat(2, 0, 1000),
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
        ];
    }
}
