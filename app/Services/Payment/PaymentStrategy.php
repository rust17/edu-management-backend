<?php

namespace App\Services\Payment;

use App\Models\Invoice;

interface PaymentStrategy
{
    /**
     * 处理支付
     *
     * @param Invoice $invoice
     * @param array $params
     * @return array
     */
    public function pay(Invoice $invoice, array $params): array;

    /**
     * 获取支付平台标识
     *
     * @return string
     */
    public function getPlatform(): string;

    /**
     * 获取支付方式
     *
     * @return string
     */
    public function getMethod(): string;
}
