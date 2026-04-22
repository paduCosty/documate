<?php

namespace App\Http\Requests\Extraction;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates creating or updating a custom extraction template.
 *
 * System templates (is_system = true) cannot be created via this request —
 * they are seeded and managed in code.
 */
class StoreExtractionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:100'],
            'description'     => ['nullable', 'string', 'max:500'],
            'prompt_template' => [
                'required',
                'string',
                'max:10000',
                'regex:/\{pdf_text\}/',       // must contain {pdf_text}
                'regex:/\{output_schema\}/',   // must contain {output_schema}
            ],
            'output_schema'   => ['required', 'array'],
            'output_schema.type' => ['required', 'string', 'in:object,array'],
        ];
    }

    public function messages(): array
    {
        return [
            'prompt_template.regex' => 'The prompt template must contain both {pdf_text} and {output_schema} placeholders.',
            'output_schema.required' => 'An output schema is required.',
            'output_schema.type.in'  => 'Output schema type must be "object" or "array".',
        ];
    }
}
