<?php

namespace App\Http\Requests\Api\SupportTicket;

use App\Http\Requests\Api\ApiFormRequest;

class StoreSupportTicketRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
