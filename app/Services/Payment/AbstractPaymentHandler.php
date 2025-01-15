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
     * 处理支付流程
     *
     * @param Invoice $invoice
     * @param array $params
     * @return array
     */
    public function handle(Invoice $invoice, array $params): array
    {
        try {
            // 1. 调用具体支付策略进行支付
            $result = $this->strategy->pay($invoice, $params);

            // 2. 更新订单状态和创建支付记录
            $this->updatePaymentStatus($invoice, $result);

            return ['success' => true, 'message' => '支付成功'];
        } catch (Exception $e) {
            $this->handleError($e, $invoice, $result ?? []);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 更新支付状态
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

            throw new Exception('您已支付成功，但是更新状态失败，请联系管理员');
        }
    }

    /**
     * 处理错误
     *
     * @param Exception $e
     * @param Invoice $invoice
     * @param array $result
     */
    protected function handleError(Exception $e, Invoice $invoice, array $result): void
    {
        if (!empty($result['is_paid'])) {
            // 如果已经支付成功但更新状态失败
            Log::error(sprintf(
                <<<EOT
pay-error-update-invoice-status: %s 可以执行以下命令修复数据
php artisan fix-invoice-status --invoice_id=%s --transaction_no=%s --transaction_fee=%s
EOT,
                $e->getMessage(),
                $invoice->id,
                $result['transaction_no'],
                $result['transaction_fee']
            ));

            return;
        }

        // 支付过程中的其他错误
        Log::error('payment-error: ' . $e->getMessage(), [
            'invoice_id' => $invoice->id,
            'params' => $result
        ]);
    }
}
