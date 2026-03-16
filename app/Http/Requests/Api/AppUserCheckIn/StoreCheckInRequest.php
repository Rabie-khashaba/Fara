<?php

namespace App\Http\Requests\Api\AppUserCheckIn;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCheckInRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCityCheckInRoute = $this->route('city') !== null;

        return [
            'latitude' => [$isCityCheckInRoute ? 'nullable' : 'required', 'numeric', 'between:-90,90'],
            'longitude' => [$isCityCheckInRoute ? 'nullable' : 'required', 'numeric', 'between:-180,180'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(['restaurant', 'cafe', 'mall', 'other'])],
            'checked_in_at' => ['nullable', 'date'],
        ];
    }
}
