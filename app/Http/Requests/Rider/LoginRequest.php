<?php

namespace App\Http\Requests\Rider;

use App\Http\Requests\ApiRequest;

class LoginRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'     => ['required', 'email'],
            'password'  => ['required', 'string'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }
}
