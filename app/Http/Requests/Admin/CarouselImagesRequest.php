<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CarouselImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Choose at least one image to upload.',
            'images.*.image' => 'Each upload must be a valid JPG, PNG, or WebP image.',
            'images.*.mimes' => 'Each image must be a JPG, JPEG, PNG, or WebP file.',
            'images.*.max' => 'Each image may not be larger than 5 MB.',
            'images.*.uploaded' => 'One of the images could not be uploaded. Check the file size and try again.',
        ];
    }
}
