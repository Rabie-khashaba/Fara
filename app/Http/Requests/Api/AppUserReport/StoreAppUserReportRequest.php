<?php

namespace App\Http\Requests\Api\AppUserReport;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\AppUserReport;
use Illuminate\Validation\Rule;

class StoreAppUserReportRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(AppUserReport::TYPES)],
            'details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
