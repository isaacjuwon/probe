<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;
use Throwable;

final class FormRequestRulesExtractor
{
    /**
     * @param  class-string<FormRequest>  $class
     * @return array<string, mixed>
     */
    public function extract(string $class): array
    {
        try {
            $reflection = new ReflectionClass(objectOrClass: $class);
            $instance = $reflection->newInstanceWithoutConstructor();

            if (method_exists(object_or_class: $instance, method: 'rules')) {
                $rules = $instance->rules();

                return is_array(value: $rules) ? $rules : [];
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }
}
