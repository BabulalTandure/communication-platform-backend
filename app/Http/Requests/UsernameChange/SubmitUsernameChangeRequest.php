<?php

namespace App\Http\Requests\UsernameChange;

use Illuminate\Foundation\Http\FormRequest;

class SubmitUsernameChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'unique:users,username',
                'different:' . $this->user()?->username,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_username.different' => 'The requested username must be different from your current username.',
            'requested_username.unique' => 'The requested username is already taken.',
        ];
    }
}
