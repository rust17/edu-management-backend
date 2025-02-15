<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\AbstractPaymentHandler;
use App\Services\Payment\PaymentHandlerFactory;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentHandlerFactoryTest extends TestCase
{
    /**
     * @testdox Create Omise payment handler
     */
    public function testCreateOmiseHandler()
    {
        $handler = PaymentHandlerFactory::create('omise');
        $this->assertInstanceOf(AbstractPaymentHandler::class, $handler);
    }

    /**
     * @testdox Create unsupported payment handler
     */
    public function testCreateUnsupportedHandler()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment platform');

        PaymentHandlerFactory::create('unsupported');
    }
}
