<?php

namespace App\Http\Requests\Traits;

use Illuminate\Auth\Access\AuthorizationException;

trait FailedAuthorizationTrait
{
    protected $errorMessage;

    /**
     * 处理授权失败的情况
     *
     * @throws AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException($this->errorMessage ?? '您没有权限执行此操作');
    }
}
