<?php

namespace App\Http\Requests\Api\AppUserPost;

use App\Http\Requests\Api\ApiFormRequest;

class UpdatePostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
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
        ];
    }
}
