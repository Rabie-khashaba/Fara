<?php

namespace App\Http\Requests\Api\SupportTicket;

use App\Http\Requests\Api\ApiFormRequest;

class StoreSupportTicketMessageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
