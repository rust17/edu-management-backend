<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\OmisePayRequest;
use App\Services\Payment\PaymentHandlerFactory;

class PaymentController extends Controller
{
    public function omisePay(OmisePayRequest $request)
    {
        $invoice = Invoice::findOrFail($request->invoice_id);

        if ($invoice->status === Invoice::STATUS_PAID) {
            return $this->error('订单已支付', 1, 422);
        }

        $result = PaymentHandlerFactory::create('omise')->handle($invoice, ['token' => $request->token]);

        if (!$result['success']) {
            return $this->error($result['message'], 1, 500);
        }

        return $this->success($result['message']);
    }
}
