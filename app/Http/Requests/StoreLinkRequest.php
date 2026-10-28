<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesUrl;
use App\Rules\AllowedDestination;
use App\Rules\AvailableAlias;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación para crear un link, compartida entre la web y la API.
 */
class StoreLinkRequest extends FormRequest
{
    use NormalizesUrl;

    public function authorize(): bool
    {
        return (bool) $this->user()?->isApproved();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'url' => $this->normalizeUrl($this->input('url')),
            'alias' => filled($this->input('alias')) ? trim((string) $this->input('alias')) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https', new AllowedDestination],
            'alias' => [
                'nullable', 'string',
                'min:'.config('shortener.alias.min'),
                'max:'.config('shortener.alias.max'),
                'regex:'.config('shortener.alias.pattern'),
                new AvailableAlias,
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Pegá la URL que querés acortar.',
            'url.url' => 'Eso no parece una URL válida.',
            'alias.regex' => 'El alias solo puede tener letras, números, guiones y guiones bajos.',
            'expires_at.after' => 'La fecha de vencimiento tiene que ser futura.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'url' => 'URL',
            'alias' => 'alias',
            'expires_at' => 'vencimiento',
        ];
    }
}
