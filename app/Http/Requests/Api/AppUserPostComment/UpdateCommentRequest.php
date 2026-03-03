<?php

namespace App\Http\Requests\Api\AppUserPostComment;

use App\Http\Requests\Api\ApiFormRequest;

class UpdateCommentRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:5000'],
        ];
    }
}
