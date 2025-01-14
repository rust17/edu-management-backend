<?php

namespace App\Services;

use OmiseCharge;
use OmiseException;
use Exception;

define('OMISE_API_VERSION', config('services.omise.api_version'));

class OmisePaymentService
{
    /**
     * 使用 token 向信用卡收款
     *
     * @param string $amount 金额
     * @param string $currency 货币
     * @param string $description 描述
     * @param string $token 信用卡 token
     *
     * @return OmiseCharge
     *
     * @throws Exception|OmiseException
     */
    public static function chargeCardWithToken(
        string $amount,
        string $currency,
        string $description,
        string $token
    ): OmiseCharge {
        return OmiseCharge::create(
            [
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'card' => $token,
            ],
            config('services.omise.public_key'),
            config('services.omise.secret_key')
        );
    }
}
