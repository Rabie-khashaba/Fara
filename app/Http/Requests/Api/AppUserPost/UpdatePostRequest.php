<?php

namespace App\Http\Requests\Api\AppUserPost;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_ghost')) {
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
    }

    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'array', 'max:10'],
            'image.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'background_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'is_ghost' => ['nullable', 'boolean'],
        ];
    }
}