<?php

namespace App\Http\Requests\Rider;

use App\Http\Requests\ApiRequest;

class RegisterRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'    => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'address'      => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'fcm_token'    => ['nullable', 'string'],
        ];
    }
}
