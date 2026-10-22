<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Actions;

use Isaacjuwon\Probe\Contracts\RequestRepository;

/**
 * Action: Persists a saved request collection entry.
 */
final class SaveRequest
{
    public function __construct(
        private readonly RequestRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(string $id, array $data): bool
    {
        return $this->repository->save(id: $id, data: $data);
    }
}
