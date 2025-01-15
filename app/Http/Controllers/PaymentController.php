<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\OmisePayRequest;
use App\Http\Services\OmisePaymentService;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentController extends Controller
{
    public function omisePay(OmisePayRequest $request)
    {
        $invoice = Invoice::find($request->invoice_id);

        try {
            // 向 omise 发送支付请求
            $charge = OmisePaymentService::chargeCardWithToken(
                amount: (string) $invoice->amount,
                currency: 'JPY',
                description: 'Payment for invoice ' . $invoice->no . ' - ' . $invoice->course->name,
                token: $request->token
            );
        } catch (Exception $e) {
            // 如果支付失败，则返回错误信息
            return $this->error($e->getMessage(), 1, 500);
        }

        // 如果支付成功，则更新订单状态，同时创建支付记录
        try {
            DB::beginTransaction();

            $invoice->update(['status' => Invoice::STATUS_PAID]);

            Payment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $invoice->student_id,
                'amount' => $invoice->amount,
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'transaction_no' => $charge['id'],
                'transaction_fee' => $charge['amount'] - $charge['net'],
                'payment_platform' => Payment::PAYMENT_PLATFORM_OMISE,
                'payment_method' => Payment::PAYMENT_METHOD_CARD,
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            // 已支付成功，但是更新状态失败，人工干预
            return $this->error($e->getMessage(), 1, 500);
        }

        return $this->success('Payment successful');
    }
}
