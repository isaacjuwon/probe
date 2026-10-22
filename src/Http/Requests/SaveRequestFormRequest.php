<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SaveRequestFormRequest extends FormRequest
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
            'id' => ['required', 'string'],
            'data' => ['required', 'array'],
        ];
    }
}
