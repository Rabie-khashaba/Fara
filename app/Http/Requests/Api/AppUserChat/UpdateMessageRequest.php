<?php

namespace App\Http\Requests\Api\AppUserChat;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateMessageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
