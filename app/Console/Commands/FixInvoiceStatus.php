<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Payment\PaymentHandlerFactory;
use Exception;
use Illuminate\Console\Command;

class FixInvoiceStatus extends Command
{
    /**
     * 命令名称和参数
     *
     * @var string
     */
    protected $signature = 'fix-invoice-status
                            {--invoice_id= : 需要修复的账单ID}
                            {--transaction_no= : 支付平台交易号}
                            {--transaction_fee= : 交易手续费}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '修复支付成功但状态未更新的账单';

    /**
     * 执行命令
     */
    public function handle()
    {
        $invoiceId = $this->option('invoice_id');
        $transactionNo = $this->option('transaction_no');
        $transactionFee = $this->option('transaction_fee');

        if (!$invoiceId || !$transactionNo || !$transactionFee) {
            $this->error('缺少必要参数');
            return 1;
        }

        if (!$invoice = Invoice::find($invoiceId)) {
            $this->error('账单不存在');
            return 1;
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            $this->info('账单已经是支付状态，无需修复');
            return 0;
        }

        try {
            // 使用支付处理器的更新状态方法
            PaymentHandlerFactory::create('omise')->updatePaymentStatus($invoice, [
                'transaction_no' => $transactionNo,
                'transaction_fee' => $transactionFee,
            ]);

            $this->info('账单状态修复成功');
            return 0;
        } catch (Exception $e) {
            $this->error('修复失败: ' . $e->getMessage());
            return 1;
        }
    }
}
