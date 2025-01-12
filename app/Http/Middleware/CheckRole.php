<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Traits\JsonResponse;

class CheckRole
{
    use JsonResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $roles 以英文逗号分隔的角色
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $roles)
    {
        if (!in_array($request->user()->role, explode(',', $roles))) {
            return $this->error('您没有权限执行此操作', 1, 403);
        }

        return $next($request);
    }
}
