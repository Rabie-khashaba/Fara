<?php

namespace App\Http\Requests\Api\AppUserAuth;

use App\Http\Requests\Api\ApiFormRequest;

class ForgotPasswordVerifyOtpRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'otp' => ['required', 'string', 'size:4'],
        ];
    }
}
