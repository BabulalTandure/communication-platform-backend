<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'message_type' => ['required', 'string', 'in:text,image,voice'],
        ];

        $type = $this->input('message_type');

        if ($type === 'text') {
            $rules['message_text'] = ['required', 'string', 'max:5000'];
        } elseif ($type === 'image') {
            $rules['file'] = ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'];
            $rules['message_text'] = ['nullable', 'string', 'max:1000'];
        } elseif ($type === 'voice') {
            $rules['file'] = ['required', 'file', 'mimetypes:audio/mpeg,audio/ogg,audio/wav,audio/x-wav,audio/mp4,audio/aac,audio/webm,audio/x-m4a,audio/m4a,audio/mp3,audio/x-mp3,application/octet-stream', 'max:10240'];
            $rules['message_text'] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }
}
