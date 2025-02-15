<?php

namespace App\Services\Payment;

use App\Models\Invoice;

interface PaymentStrategy
{
    /**
     * Handle payment
     *
     * @param Invoice $invoice
     * @param array $params
     * @return array
     */
    public function pay(Invoice $invoice, array $params): array;

    /**
     * Get payment platform identifier
     *
     * @return string
     */
    public function getPlatform(): string;

    /**
     * Get payment method
     *
     * @return string
     */
    public function getMethod(): string;
}
