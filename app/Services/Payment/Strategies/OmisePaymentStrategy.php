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
     * Validate payment result
     *
     * @param OmiseCharge $charge
     * @throws Exception
     */
    protected function validateCharge(OmiseCharge $charge): void
    {
        $errorMessages = [
            'insufficient_fund' => 'Payment failed, insufficient funds',
            'insufficient_balance' => 'Payment failed, insufficient balance',
            'failed_fraud_check' => 'Payment failed, the card is marked as fraudulent, it is recommended to use another credit card',
            'confirmed_amount_mismatch' => 'Payment failed, the payment gateway amount does not match the order amount, please try again later',
            'failed_processing' => 'Payment failed, payment gateway processing failed, please try again later',
            'invalid_account_number' => 'Payment failed, card number or username is incorrect, please use another credit card',
            'invalid_account' => 'Payment failed, card number or username is incorrect, please use another credit card',
            'payment_cancelled' => 'Payment failed, you have cancelled the payment',
            'payment_rejected' => 'Payment failed, rejected by the issuing bank',
            'stolen_or_lost_card' => 'Payment failed, the card is stolen or lost, please use another credit card',
            'timeout' => 'Payment failed, payment gateway timeout, please try again later',
        ];

        if ($errMessage = $errorMessages[$charge['failure_code'] ?? ''] ?? '') {
            throw new Exception($errMessage);
        }
    }
}
