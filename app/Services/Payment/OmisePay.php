<?php

namespace App\Services\Payment;

use OmiseCharge;
use OmiseException;
use Exception;

define('OMISE_API_VERSION', config('services.omise.api_version'));

class OmisePay
{
    /**
     * Charge card with token
     *
     * @param string $amount Amount
     * @param string $currency Currency
     * @param string $description Description
     * @param string $token Credit card token
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
