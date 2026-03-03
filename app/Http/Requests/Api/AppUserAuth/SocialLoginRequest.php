<?php

namespace App\Http\Requests\Api\AppUserAuth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', Rule::in(['google', 'facebook', 'apple'])],
            'token' => ['required', 'string'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', 'alpha_dash'],
            'phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
