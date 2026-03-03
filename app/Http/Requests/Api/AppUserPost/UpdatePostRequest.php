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
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'required', 'string', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
