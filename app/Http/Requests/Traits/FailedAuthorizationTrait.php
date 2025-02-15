<?php

namespace App\Http\Requests\Traits;

use Illuminate\Auth\Access\AuthorizationException;

trait FailedAuthorizationTrait
{
    protected $errorMessage;

    /**
     * Handle a failed authorization attempt.
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException($this->errorMessage ?? 'You do not have permission to perform this operation');
    }
}
