<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'role' => 'required|in:' . User::ROLE_TEACHER . ',' . User::ROLE_STUDENT
        ];
    }

    public function messages(): array
    {
        return [
            'email.*' => '用户名必须是邮箱格式',
            'password.required' => '密码不能为空',
            'role.required' => '角色不能为空',
            'role.in' => '角色必须是老师或学生'
        ];
    }
}
