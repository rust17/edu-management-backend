<?php

namespace Tests\Unit\Payment\Strategies;

use App\Models\Payment;
use App\Services\Payment\Strategies\OmisePaymentStrategy;
use Exception;
use Tests\Unit\Payment\PaymentTestCase;
use Mockery;

class OmisePaymentStrategyTest extends PaymentTestCase
{
    private OmisePaymentStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->strategy = new OmisePaymentStrategy();
    }

    /**
     * @testdox 使用 Omise 支付成功
     */
    public function testPaySuccess()
    {
        // Mock OmisePay 服务
        $charge = Mockery::mock('overload:OmiseCharge', 'ArrayAccess');
        $charge->shouldReceive('create')
            ->once()
            ->andReturn($charge);

        $charge->shouldReceive('offsetGet')
            ->with('id')
            ->andReturn('chrg_test_123');
        $charge->shouldReceive('offsetGet')
            ->with('amount')
            ->andReturn(100000);
        $charge->shouldReceive('offsetGet')
            ->with('net')
            ->andReturn(97000);
        $charge->shouldReceive('offsetGet')
            ->with('failure_code')
            ->andReturnNull();
        $charge->shouldReceive('offsetExists')
            ->andReturn(true);

        // Mock OmisePay::chargeCardWithToken
        $this->mock('overload:OmisePay')
            ->shouldReceive('chargeCardWithToken')
            ->once()
            ->with(
                $this->invoice->amount * 100,
                'JPY',
                "Payment for invoice {$this->invoice->no} - {$this->invoice->course->name}",
                'tok_test_123'
            )
            ->andReturn($charge);

        $result = $this->strategy->pay($this->invoice, ['token' => 'tok_test_123']);

        $this->assertTrue($result['is_paid']);
        $this->assertEquals('chrg_test_123', $result['transaction_no']);
        $this->assertEquals(30, $result['transaction_fee']);
    }

    /**
     * @testdox 使用 Omise 支付失败，余额不足
     */
    public function testPayFailureWithInsufficientFund()
    {
        // Mock OmiseCharge 服务
        $charge = Mockery::mock('overload:OmiseCharge', 'ArrayAccess');
        $charge->shouldReceive('offsetGet')
            ->with('failure_code')
            ->andReturn('insufficient_fund');

        $charge->shouldReceive('create')
            ->once()
            ->andReturn($charge);

        $charge->shouldReceive('offsetGet')
            ->with('failure_code')
            ->andReturn('insufficient_fund');
        $charge->shouldReceive('offsetExists')
            ->andReturn(true);

        // Mock OmisePay::chargeCardWithToken
        $this->mock('overload:OmisePay')
            ->shouldReceive('chargeCardWithToken')
            ->once()
            ->andReturn($charge);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('支付失败，余额不足');

        $this->strategy->pay($this->invoice, ['token' => 'tok_test_123']);
    }

    /**
     * @testdox 获取支付平台
     */
    public function testGetPlatform()
    {
        $this->assertEquals(
            Payment::PAYMENT_PLATFORM_OMISE,
            $this->strategy->getPlatform()
        );
    }

    /**
     * @testdox 获取支付方式
     */
    public function testGetMethod()
    {
        $this->assertEquals(
            Payment::PAYMENT_METHOD_CARD,
            $this->strategy->getMethod()
        );
    }
}
