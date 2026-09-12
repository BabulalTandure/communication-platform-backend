<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class CreatePrivateChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id', 'different:' . $this->user()?->id],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.different' => 'You cannot start a private chat with yourself.',
        ];
    }
}
