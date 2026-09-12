<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['nullable', 'string', 'in:user,admin'],
            'status' => ['nullable', 'string', 'in:active,disabled'],
        ];
    }
}
