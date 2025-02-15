<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentHandler
{
    protected PaymentStrategy $strategy;

    public function __construct(PaymentStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * Handle payment process
     *
     * @param Invoice $invoice
     * @param array $params
     * @return array
     */
    public function handle(Invoice $invoice, array $params): array
    {
        try {
            // 1. Call the specific payment strategy for payment
            $result = $this->strategy->pay($invoice, $params);

            // 2. Update order status and create payment record
            $this->updatePaymentStatus($invoice, $result);

            return ['success' => true, 'message' => 'Payment successful'];
        } catch (Exception $e) {
            $this->handleError($e, $invoice, $result ?? []);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update payment status
     *
     * @param Invoice $invoice
     * @param array $result
     * @throws Exception
     */
    public function updatePaymentStatus(Invoice $invoice, array $result): void
    {
        try {
            DB::beginTransaction();

            $invoice->update(['status' => Invoice::STATUS_PAID]);

            Payment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'amount' => $invoice->amount,
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'transaction_no' => $result['transaction_no'],
                'transaction_fee' => $result['transaction_fee'],
                'payment_platform' => $this->strategy->getPlatform(),
                'payment_method' => $this->strategy->getMethod(),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw new Exception('You have successfully paid, but the update status failed, please contact the administrator');
        }
    }

    /**
     * Handle errors
     *
     * @param Exception $e
     * @param Invoice $invoice
     * @param array $result
     */
    protected function handleError(Exception $e, Invoice $invoice, array $result): void
    {
        if (!empty($result['is_paid'])) {
            // If payment is successful but updating status fails
            Log::error(sprintf(
                <<<EOT
pay-error-update-invoice-status: %s You can execute the following command to fix the data
php artisan fix-invoice-status --invoice_id=%s --transaction_no=%s --transaction_fee=%s
EOT,
                $e->getMessage(),
                $invoice->id,
                $result['transaction_no'],
                $result['transaction_fee']
            ));

            return;
        }

        // Other errors during the payment process
        Log::error('payment-error: ' . $e->getMessage(), [
            'invoice_id' => $invoice->id,
            'params' => $result
        ]);
    }
}
