<?php

namespace App\Http\Requests\Api\AppUserAuth;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class SocialRegistrationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('app_users', 'username')],
            'phone' => ['required', 'string', 'max:30', Rule::unique('app_users', 'phone')],
        ];
    }
}
