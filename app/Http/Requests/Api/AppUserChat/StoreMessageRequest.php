<?php

namespace App\Http\Requests\Api\AppUserChat;

use App\Http\Requests\Api\ApiFormRequest;

class StoreMessageRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:5000', 'required_without_all:image,video,contact,latitude,longitude'],
            'type' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'array', 'max:10', 'required_without_all:body,video,contact,latitude,longitude'],
            'image.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska', 'max:51200', 'required_without_all:body,image,contact,latitude,longitude'],
            'contact' => ['nullable', 'array', 'required_without_all:body,image,video,latitude,longitude'],
            'contact.name' => ['required_with:contact', 'string', 'max:255'],
            'contact.phone' => ['required_with:contact', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'required_with:longitude', 'required_without_all:body,image,video,contact'],
            'longitude' => ['nullable', 'numeric', 'required_with:latitude', 'required_without_all:body,image,video,contact'],
        ];
    }
}
