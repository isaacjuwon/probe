<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Throwable;

final class ProbeUserIndexController
{
    public function __invoke(): JsonResponse
    {
        Gate::authorize(ability: 'viewProbe');

        /** @var class-string<Model>|null $userModel */
        $userModel = config(key: 'auth.providers.users.model');

        if (! is_string(value: $userModel) || ! class_exists(class: $userModel)) {
            return new JsonResponse(data: ['data' => []]);
        }

        try {
            /** @var Collection<int, Model> $users */
            $users = $userModel::query()
                ->limit(value: 20)
                ->get();

            $data = $users->map(callback: static function (Model $user): array {
                /** @var string|null $email */
                $email = $user->getAttribute(key: 'email');
                /** @var string|null $name */
                $name = $user->getAttribute(key: 'name');

                return [
                    'id' => (string) $user->getKey(),
                    'name' => $name ?? 'User #'.$user->getKey(),
                    'email' => $email,
                ];
            })->all();

            return new JsonResponse(data: ['data' => $data]);
        } catch (Throwable) {
            return new JsonResponse(data: ['data' => []]);
        }
    }
}
