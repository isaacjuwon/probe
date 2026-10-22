<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Discovery;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

final class ControllerReflector
{
    public function getReflection(mixed $action): ?ReflectionFunctionAbstract
    {
        if ($action instanceof Closure) {
            return new ReflectionFunction(function: $action);
        }

        if (is_array(value: $action) && count(value: $action) >= 2) {
            [$class, $method] = $action;

            if (is_object(value: $class)) {
                $class = get_class(object: $class);
            }

            if (is_string(value: $class) && class_exists(class: $class) && method_exists(object_or_class: $class, method: (string) $method)) {
                return new ReflectionMethod(objectOrMethod: $class, method: (string) $method);
            }
        }

        if (is_string(value: $action) && str_contains(haystack: $action, needle: '@')) {
            [$class, $method] = explode(separator: '@', string: $action, limit: 2);

            if (class_exists(class: $class) && method_exists(object_or_class: $class, method: $method)) {
                return new ReflectionMethod(objectOrMethod: $class, method: $method);
            }
        }

        if (is_string(value: $action) && class_exists(class: $action) && method_exists(object_or_class: $action, method: '__invoke')) {
            return new ReflectionMethod(objectOrMethod: $action, method: '__invoke');
        }

        return null;
    }

    /**
     * @return class-string<FormRequest>|null
     */
    public function getFormRequestClass(mixed $action): ?string
    {
        $reflection = $this->getReflection(action: $action);

        if ($reflection === null) {
            return null;
        }

        try {
            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $typeName = $type->getName();

                    if (is_subclass_of(object_or_class: $typeName, class: FormRequest::class)) {
                        return $typeName;
                    }
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }
}
