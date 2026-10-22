<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Contracts;

use Illuminate\Support\Collection;

interface RequestRepository
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection;

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(string $id, array $data): bool;

    public function delete(string $id): bool;
}
