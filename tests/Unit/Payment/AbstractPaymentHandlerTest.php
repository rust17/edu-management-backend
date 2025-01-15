<?php

namespace Tests\Unit\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payment\AbstractPaymentHandler;
use App\Services\Payment\PaymentStrategy;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Unit\Payment\PaymentTestCase;
use Mockery;

class AbstractPaymentHandlerTest extends PaymentTestCase
{
    private $mockStrategy;
    private AbstractPaymentHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock PaymentStrategy
        $this->mockStrategy = Mockery::mock(PaymentStrategy::class);
        $this->mockStrategy->shouldReceive('getPlatform')
            ->andReturn(Payment::PAYMENT_PLATFORM_OMISE);
        $this->mockStrategy->shouldReceive('getMethod')
            ->andReturn(Payment::PAYMENT_METHOD_CARD);

        // 创建匿名类实例
        $this->handler = new class($this->mockStrategy) extends AbstractPaymentHandler {};
    }

    /**
     * @testdox 支付成功
     */
    public function testHandleSuccess()
    {
        $this->mockStrategy->shouldReceive('pay')
            ->once()
            ->andReturn([
                'is_paid' => true,
                'transaction_no' => 'chrg_test_123',
                'transaction_fee' => 30
            ]);

        $result = $this->handler->handle($this->invoice, ['token' => 'tok_test_123']);

        $this->assertTrue($result['success']);
        $this->assertEquals('支付成功', $result['message']);

        // 验证数据库更新
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->fresh()->status);

        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('chrg_test_123', $payment->transaction_no);
        $this->assertEquals(30, $payment->transaction_fee);
    }

    /**
     * @testdox 支付失败
     */
    public function testHandlePaymentFailure()
    {
        $this->mockStrategy->shouldReceive('pay')
            ->once()
            ->andThrow(new Exception('支付失败，余额不足'));

        $result = $this->handler->handle($this->invoice, ['token' => 'tok_test_123']);

        $this->assertFalse($result['success']);
        $this->assertEquals('支付失败，余额不足', $result['message']);

        // 验证数据库未更新
        $this->assertEquals(Invoice::STATUS_PENDING, $this->invoice->fresh()->status);
        $this->assertEquals(0, Payment::where('invoice_id', $this->invoice->id)->count());
    }

    /**
     * @testdox 更新状态失败
     */
    public function testHandleUpdateStatusFailure()
    {
        $invoice = Mockery::mock(Invoice::class)->makePartial();

        $this->mockStrategy->shouldReceive('pay')
            ->once()
            ->andReturn([
                'is_paid' => true,
                'transaction_no' => 'chrg_test_123',
                'transaction_fee' => 30
            ]);

        // 模拟数据库错误
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        $invoice->shouldReceive('update')->once()->andThrow(new Exception('Database error'));

        // 验证错误日志
        Log::shouldReceive('error')->once()->with(Mockery::pattern('/^pay-error-update-invoice-status/'));

        /** @var Invoice $invoice */
        $result = $this->handler->handle($invoice, ['token' => 'tok_test_123']);

        $this->assertFalse($result['success']);
        $this->assertEquals('您已支付成功，但是更新状态失败，请联系管理员', $result['message']);
    }
}
