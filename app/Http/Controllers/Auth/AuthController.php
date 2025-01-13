<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\Business;

class AuthController extends Controller
{
    /**
     * 登录
     *
     * @param AuthRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(AuthRequest $request)
    {
        $user = User::where('email', $request->email)
            ->where('role', $request->role)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('用户名或密码错误', Business::LOGIN_FAILED->value, 401);
        }

        return $this->success('登录成功', [
            'access_token' => $user->createToken('auth_token')->accessToken,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }

    /**
     * 登出
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->success('已成功登出');
    }
}
