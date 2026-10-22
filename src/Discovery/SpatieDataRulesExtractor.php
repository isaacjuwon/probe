<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Throwable;

final class SpatieDataRulesExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(?ReflectionFunctionAbstract $reflection): array
    {
        if ($reflection === null) {
            return [];
        }

        if (! class_exists(class: 'Spatie\LaravelData\Data')) {
            return [];
        }

        try {
            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (is_subclass_of(object_or_class: $typeName, class: 'Spatie\LaravelData\Data')) {
                        if (method_exists(object_or_class: $typeName, method: 'rules')) {
                            $rules = $typeName::rules();

                            return is_array(value: $rules) ? $rules : [];
                        }
                    }
                }
            }
        } catch (Throwable) {
            return [];
        }

        return [];
    }
}
