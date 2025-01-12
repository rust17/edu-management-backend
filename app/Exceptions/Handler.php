<?php

namespace App\Exceptions;

use App\Traits\JsonResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Throwable;

class Handler extends ExceptionHandler
{
    use JsonResponse;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof ValidationException) {
                return $this->error('验证失败', 1, 422, $e->errors());
            }

            if ($e instanceof AuthorizationException) {
                return $this->error($e->getMessage(), 1, 403);
            }

            if ($e instanceof AuthenticationException) {
                return $this->error($e->getMessage(), 1, 401);
            }

            // 其他异常
            return $this->error($e->getMessage(), 2, 500);
        }

        return parent::render($request, $e);
    }
}
