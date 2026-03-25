<?php

namespace App\Http\Requests\Api\AppUserChat;

use App\Http\Requests\Api\ApiFormRequest;

class StartDirectConversationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipient_app_user_id' => ['required', 'integer', 'exists:app_users,id'],
            'body' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auth_user_id' => $this->user()?->id,
        ]);
    }
}
