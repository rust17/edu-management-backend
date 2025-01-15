<?php

namespace App\Services\Payment\Strategies;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payment\PaymentStrategy;
use App\Services\Payment\OmisePay;
use OmiseCharge;
use Exception;

class OmisePaymentStrategy implements PaymentStrategy
{
    public function pay(Invoice $invoice, array $params): array
    {
        $charge = OmisePay::chargeCardWithToken(
            amount: $invoice->amount * 100,
            currency: 'JPY',
            description: "Payment for invoice {$invoice->no} - {$invoice->course->name}",
            token: $params['token']
        );

        $this->validateCharge($charge);

        return [
            'is_paid' => true,
            'transaction_no' => $charge['id'],
            'transaction_fee' => floatval($charge['amount'] - $charge['net']) / 100,
        ];
    }

    public function getPlatform(): string
    {
        return Payment::PAYMENT_PLATFORM_OMISE;
    }

    public function getMethod(): string
    {
        return Payment::PAYMENT_METHOD_CARD;
    }

    /**
     * 验证支付结果
     *
     * @param OmiseCharge $charge
     * @throws Exception
     */
    protected function validateCharge(OmiseCharge $charge): void
    {
        $errorMessages = [
            'insufficient_fund' => '支付失败，余额不足',
            'insufficient_balance' => '支付失败，余额不足',
            'failed_fraud_check' => '支付失败，卡被标记为欺诈，建议使用其他信用卡',
            'confirmed_amount_mismatch' => '支付失败，支付通道金额与订单金额不匹配，请稍后重试',
            'failed_processing' => '支付失败，支付通道处理失败，请稍后重试',
            'invalid_account_number' => '支付失败，卡号或用户名错误，请使用其他信用卡',
            'invalid_account' => '支付失败，卡号或用户名错误，请使用其他信用卡',
            'payment_cancelled' => '支付失败，您已取消支付',
            'payment_rejected' => '支付失败，被发卡行拒绝',
            'stolen_or_lost_card' => '支付失败，卡被盗或丢失，请使用其他信用卡',
            'timeout' => '支付失败，支付通道超时，请稍后重试',
        ];

        if ($errMessage = $errorMessages[$charge['failure_code'] ?? ''] ?? '') {
            throw new Exception($errMessage);
        }
    }
}
