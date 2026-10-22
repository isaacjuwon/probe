<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RelayFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'string', 'in:GET,POST,PUT,PATCH,DELETE,OPTIONS,HEAD'],
            'url' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'body' => ['nullable'],
            'query' => ['nullable', 'array'],
            'auth_type' => ['nullable', 'string'],
            'auth_payload' => ['nullable', 'array'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'dry_run' => ['nullable', 'boolean'],
        ];
    }
}
