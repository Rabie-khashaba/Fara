<?php

namespace App\Http\Requests\Api\AppUserChat;

use App\Http\Requests\Api\ApiFormRequest;

class StoreMessageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:50'],
        ];
    }
}
