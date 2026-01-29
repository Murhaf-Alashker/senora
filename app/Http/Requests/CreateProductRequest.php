<?php

namespace App\Http\Requests;

use App\Enum\MediaType;
use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->guard('api')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255','min:3'],
            'description' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric','min:1'],
            'custom_tailoring' => ['required', 'boolean'],
            'colors' => ['required', 'array'],
            'colors.*' => ['required', 'string', 'max:255','min:3'],
            'sizes' => ['required', 'array'],
            'sizes.*' => ['required', 'numeric','between:30,50','multiple_of:2'],
            'categories' => ['required', 'array'],
            'categories.*' => ['required', 'exists:categories,ulid'],
            'media' => ['sometimes', 'array'],
            'media.*' => ['required', 'file','mimes:'. implode(',',MediaType::images())],
            'wanted_media' => ['sometimes', 'array'],
            'wanted_media.*' => ['required', 'exists:media,id'],
        ];
    }
}
