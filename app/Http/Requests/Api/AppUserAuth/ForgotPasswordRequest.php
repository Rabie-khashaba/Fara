<?php

namespace App\Http\Requests\Api\AppUserAuth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', Rule::exists('app_users', 'phone')],
        ];
    }
}
