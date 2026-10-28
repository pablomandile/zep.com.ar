<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesUrl;
use App\Models\Link;
use App\Rules\AllowedDestination;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de un link: destino, activo y vencimiento. El código no se cambia
 * (un link corto ya compartido tiene que seguir apuntando a lo mismo).
 */
class UpdateLinkRequest extends FormRequest
{
    use NormalizesUrl;

    public function authorize(): bool
    {
        $link = $this->route('link');

        // En la API el parámetro es {code} y el controlador ya busca solo entre los links propios.
        return $link instanceof Link
            ? (bool) $this->user()?->can('update', $link)
            : (bool) $this->user()?->isApproved();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('url')) {
            $this->merge(['url' => $this->normalizeUrl($this->input('url'))]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['sometimes', 'required', 'string', 'max:2048', 'url:http,https', new AllowedDestination],
            'is_active' => ['sometimes', 'boolean'],
            'expires_at' => ['sometimes', 'nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.url' => 'Eso no parece una URL válida.',
            'expires_at.after' => 'La fecha de vencimiento tiene que ser futura.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['url' => 'URL', 'is_active' => 'activo', 'expires_at' => 'vencimiento'];
    }
}
