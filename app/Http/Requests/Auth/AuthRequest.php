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
            'email.*' => 'Username must be in email format',
            'password.required' => 'Password cannot be empty',
            'role.required' => 'Role cannot be empty',
            'role.in' => 'Role must be teacher or student'
        ];
    }
}
