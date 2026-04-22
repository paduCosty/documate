<?php

namespace App\Http\Requests\Extraction;

use App\Services\Ai\AiProviderFactory;
use App\Services\Output\OutputFormatterFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the PDF extraction upload form.
 *
 * The absolute hard ceiling (config ai.extraction.max_file_size_mb) is enforced
 * here. The per-plan ceiling is checked separately in ExtractionController::process()
 * after the usage context is resolved.
 */
class ExtractionUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hardCeilKb = config('ai.extraction.max_file_size_mb', 20) * 1024;

        return [
            'file'     => [
                'required',
                'file',
                'mimes:pdf',
                'max:' . $hardCeilKb,
            ],
            'template' => [
                'required',
                'string',
                'max:100',
            ],
            'format' => [
                'nullable',
                'string',
                Rule::in(OutputFormatterFactory::availableFormats()),
            ],
            'provider' => [
                'nullable',
                'string',
                Rule::in(AiProviderFactory::availableProviders()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a PDF file.',
            'file.mimes'    => 'Only PDF files are supported.',
            'file.max'      => 'The PDF file exceeds the maximum allowed size for your plan.',
            'template.required' => 'Please select an extraction template.',
            'format.in'     => 'Invalid output format. Supported: ' . implode(', ', OutputFormatterFactory::availableFormats()) . '.',
        ];
    }

    public function outputFormat(): string
    {
        return $this->input('format') ?? OutputFormatterFactory::defaultFormat();
    }

    public function providerOverride(): ?string
    {
        return $this->filled('provider') ? $this->input('provider') : null;
    }
}
