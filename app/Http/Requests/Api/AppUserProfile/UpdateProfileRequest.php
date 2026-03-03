<?php

namespace App\Http\Requests\Api\AppUserProfile;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $appUserId = $this->user()?->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'username' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash', Rule::unique('app_users', 'username')->ignore($appUserId)],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('app_users', 'email')->ignore($appUserId)],
            'phone' => ['sometimes', 'required', 'string', 'max:30', Rule::unique('app_users', 'phone')->ignore($appUserId)],
        ];
    }
}
