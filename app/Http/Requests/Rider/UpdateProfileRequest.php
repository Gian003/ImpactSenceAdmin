<?php

namespace App\Http\Requests\Rider;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'      => ['sometimes', 'string', 'max:255'],
            'email'          => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone_number'   => ['sometimes', 'nullable', 'string', 'max:20'],
            'address'        => ['sometimes', 'nullable', 'string'],
            'date_of_birth'  => ['sometimes', 'nullable', 'date', 'before:today'],
        ];
    }
}
