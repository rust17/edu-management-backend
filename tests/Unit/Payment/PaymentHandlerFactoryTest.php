<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\AbstractPaymentHandler;
use App\Services\Payment\PaymentHandlerFactory;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentHandlerFactoryTest extends TestCase
{
    /**
     * @testdox 创建 Omise 支付处理器
     */
    public function testCreateOmiseHandler()
    {
        $handler = PaymentHandlerFactory::create('omise');
        $this->assertInstanceOf(AbstractPaymentHandler::class, $handler);
    }

    /**
     * @testdox 创建不支持的支付处理器
     */
    public function testCreateUnsupportedHandler()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment platform');

        PaymentHandlerFactory::create('unsupported');
    }
}
