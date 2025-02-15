<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AuthRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\Business;

class AuthController extends Controller
{
    /**
     * Login
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
            return $this->error('Incorrect username or password', Business::LOGIN_FAILED->value, 401);
        }

        return $this->success('Login successful', [
            'access_token' => $user->createToken('auth_token')->accessToken,
            'token_type' => 'Bearer',
            'user' => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }

    /**
     * Logout
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->success('Logged out successfully');
    }
}
