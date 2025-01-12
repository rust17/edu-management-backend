<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(AuthRequest $request)
    {
        $user = User::where('email', $request->email)
            ->where('role', $request->role)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => '用户名或密码错误'
            ], 401);
        }

        return response()->json([
            'access_token' => $user->createToken('auth_token')->accessToken,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => '已成功登出'
        ]);
    }
}
