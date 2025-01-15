<?php

namespace App\Services\Payment;

use App\Services\Payment\Strategies\OmisePaymentStrategy;

class PaymentHandlerFactory
{
    /**
     * 创建支付处理器
     *
     * @param string $platform
     * @return AbstractPaymentHandler
     */
    public static function create(string $platform): AbstractPaymentHandler
    {
        return match ($platform) {
            'omise' => new class(new OmisePaymentStrategy) extends AbstractPaymentHandler {},
            default => throw new \InvalidArgumentException('Unsupported payment platform'),
        };
    }
}
