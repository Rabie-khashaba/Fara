<?php

namespace App\Http\Requests\Api\SupportTicket;

use App\Http\Requests\Api\ApiFormRequest;

class StoreSupportTicketByPhoneRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'exists:app_users,phone'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}
