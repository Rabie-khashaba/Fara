<?php

namespace App\Http\Requests\Api\AppUserPost;

use App\Http\Requests\Api\ApiFormRequest;

class StorePostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_ghost')) {
            if ($this->has('Place_name') && ! $this->has('place_name')) {
                $this->merge([
                    'place_name' => $this->input('Place_name'),
                ]);
            }

            return;
        }

        $value = $this->input('is_ghost');

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if (in_array($normalized, ['true', 'false'], true)) {
                $this->merge([
                    'is_ghost' => $normalized === 'true',
                ]);
            }
        }

        if ($this->has('Place_name') && ! $this->has('place_name')) {
            $this->merge([
                'place_name' => $this->input('Place_name'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'array', 'max:10'],
            'image.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'background_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_ghost' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'place_name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }
}
