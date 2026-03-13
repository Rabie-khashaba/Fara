<?php

namespace App\Http\Requests\Api\AppUserAuth;

use App\Http\Requests\Api\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
            'fcm_token' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
