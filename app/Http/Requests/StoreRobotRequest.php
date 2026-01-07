<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRobotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'language' => 'required|string|max:20|in:nelogica,python,js,other,meta-traider',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'code' => 'required|string',
            'is_active' => 'nullable|boolean',
            'parameters' => 'nullable|array',
            'parameters.*.key' => 'required|string|max:80',
            'parameters.*.label' => 'required|string|max:120',
            'parameters.*.type' => 'required|string|max:20|in:number,string,boolean,select',
            'parameters.*.value' => 'required',
            'parameters.*.default_value' => 'nullable',
            'parameters.*.required' => 'nullable|boolean',
            'parameters.*.options' => 'nullable|array',
            'parameters.*.validation_rules' => 'nullable|array',
            'parameters.*.group' => 'nullable|string',
            'parameters.*.sort_order' => 'nullable|integer',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max
            'image_titles' => 'nullable|array',
            'image_titles.*' => 'nullable|string|max:120',
            'image_captions' => 'nullable|array',
            'image_captions.*' => 'nullable|string|max:255',
            'files' => 'nullable|array',
            'files.*' => [
                'file',
                'max:10240', // 10MB max
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['psf', 'mq5'])) {
                        $fail('O arquivo deve ser do tipo .psf ou .mq5');
                    }
                },
            ],
            'file_names' => 'nullable|array',
            'file_names.*' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do robô é obrigatório.',
            'language.required' => 'A linguagem é obrigatória.',
            'language.in' => 'A linguagem deve ser uma das: nelogica, python, js, other, meta-traider.',
            'code.required' => 'O código é obrigatório.',
            'files.*.file' => 'O arquivo deve ser um arquivo válido.',
            'files.*.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }
}
