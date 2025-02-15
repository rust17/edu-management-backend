<?php

namespace Tests\Unit\Console\Commands;

use App\Models\Invoice;
use App\Models\Payment;
use Tests\Unit\Payment\PaymentTestCase;
use Illuminate\Support\Facades\Artisan;

class FixInvoiceStatusTest extends PaymentTestCase
{
    /**
     * @testdox Successfully fix invoice status
     */
    public function testFixInvoiceStatusSuccess()
    {
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // Verify command executed successfully
        $this->assertEquals(0, $exitCode);

        // Verify invoice status updated
        $this->assertEquals(Invoice::STATUS_PAID, $this->invoice->fresh()->status);

        // Verify payment record created
        $payment = Payment::where('invoice_id', $this->invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('chrg_test_123', $payment->transaction_no);
        $this->assertEquals(30, $payment->transaction_fee);
        $this->assertEquals(Payment::PAYMENT_PLATFORM_OMISE, $payment->payment_platform);
        $this->assertEquals(Payment::PAYMENT_METHOD_CARD, $payment->payment_method);
    }

    /**
     * @testdox Invoice already paid
     */
    public function testFixInvoiceAlreadyPaid()
    {
        // Set invoice status to paid
        $this->invoice->update(['status' => Invoice::STATUS_PAID]);

        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // Verify command executed successfully but no fix needed
        $this->assertEquals(0, $exitCode);

        // Verify no new payment record created
        $this->assertEquals(0, Payment::where('invoice_id', $this->invoice->id)->count());
    }

    /**
     * @testdox Invoice does not exist
     */
    public function testFixNonExistentInvoice()
    {
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => 99999,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // Verify command executed failed
        $this->assertEquals(1, $exitCode);
    }

    /**
     * @testdox Missing required parameters
     */
    public function testMissingRequiredParameters()
    {
        // Test missing transaction_no
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_fee' => 30
        ]);

        $this->assertEquals(1, $exitCode);

        // Test missing transaction_fee
        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $this->invoice->id,
            '--transaction_no' => 'chrg_test_123'
        ]);

        $this->assertEquals(1, $exitCode);

        // Test missing invoice_id
        $exitCode = Artisan::call('fix-invoice-status', [
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        $this->assertEquals(1, $exitCode);
    }

    /**
     * @testdox Database update failed
     */
    public function testDatabaseUpdateFailure()
    {
        // Create an invalid invoice ID (deleted invoice)
        $invalidInvoiceId = $this->invoice->id;
        $this->invoice->delete();

        $exitCode = Artisan::call('fix-invoice-status', [
            '--invoice_id' => $invalidInvoiceId,
            '--transaction_no' => 'chrg_test_123',
            '--transaction_fee' => 30
        ]);

        // Verify command executed failed
        $this->assertEquals(1, $exitCode);
    }
}
