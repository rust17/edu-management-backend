<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use Tests\Unit\Payment\PaymentTestCase;
use Illuminate\Support\Facades\Artisan;

class FixInvoiceStatusTest extends PaymentTestCase
{
    /**
     * @testdox 成功修复发票状态
     */
    public function testFixInvoiceStatusSuccess()
    {
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // 验证命令执行成功
        $this->assertEquals(0, $exitCode);

        // 验证发票状态已更新
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->fresh()->status);

        // 验证支付记录已创建
        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('chrg_test_123', $payment->transaction_no);
        $this->assertEquals(30, $payment->transaction_fee);
        $this->assertEquals(Payment::PAYMENT_PLATFORM_OMISE, $payment->payment_platform);
        $this->assertEquals(Payment::PAYMENT_METHOD_CARD, $payment->payment_method);
    }

    /**
     * @testdox 发票已经是支付状态
     */
    public function testFixInvoiceAlreadyPaid()
    {
        // 将发票状态设置为已支付
        $this->invoice->update(['status' => Invoice::STATUS_PAID]);

        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // 验证命令执行成功，但无需修复
        $this->assertEquals(0, $exitCode);

        // 验证没有创建新的支付记录
        $this->assertEquals(0, Payment::where('invoice_id', $this->invoice->id)->count());
    }

    /**
     * @testdox 发票不存在
     */
    public function testFixNonExistentInvoice()
    {
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => 99999,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // 验证命令执行失败
        $this->assertEquals(1, $exitCode);
    }

    /**
     * @testdox 缺少必要参数
     */
    public function testMissingRequiredParameters()
    {
        // 测试缺少 transaction_no
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_fee' => 30
        ]);

        $this->assertEquals(1, $exitCode);

        // 测试缺少 transaction_fee
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123'
        ]);

        $this->assertEquals(1, $exitCode);

        // 测试缺少 invoice_id
        $exitCode = Artisan::call('fix-invoice-status', [
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /**
     * @testdox 数据库更新失败
     */
    public function testDatabaseUpdateFailure()
    {
        // 创建一个无效的发票ID（已删除的发票）
        $invalidInvoiceId = $this->invoice->id;
        $this->invoice->delete();

        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $invalidInvoiceId,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // 验证命令执行失败
        $this->assertEquals(1, $exitCode);
    }
}
