<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Payment\PaymentHandlerFactory;
use Exception;
use Illuminate\Console\Command;

class FixInvoiceStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix-invoice-status
                            {--invoice_id= : The ID of the invoice to be fixed}
                            {--transaction_no= : Payment platform transaction number}
                            {--transaction_fee= : Transaction fee}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invoices that are paid but have not been updated';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoiceId = $this->option('invoice_id');
        $transactionNo = $this->option('transaction_no');
        $transactionFee = $this->option('transaction_fee');

        if (!$invoiceId || !$transactionNo || !$transactionFee) {
            $this->error('Missing required parameters');
            return 1;
        }

        if (!$invoice = Invoice::find($invoiceId)) {
            $this->error('Invoice does not exist');
            return 1;
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            $this->info('The invoice is already in the paid state and does not need to be fixed');
            return 0;
        }

        try {
            // Use the payment processor's update status method
            PaymentHandlerFactory::create('omise')->updatePaymentStatus($invoice, [
                'transaction_no' => $transactionNo,
                'transaction_fee' => $transactionFee,
            ]);

            $this->info('Invoice status fixed successfully');
            return 0;
        } catch (Exception $e) {
            $this->error('Fix failed: ' . $e->getMessage());
            return 1;
        }
    }
}
