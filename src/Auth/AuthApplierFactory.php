<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Auth;

use Isaacjuwon\Probe\Contracts\AuthApplier;
use Isaacjuwon\Probe\Data\RelayRequest;
use Isaacjuwon\Probe\Enums\AuthType;

/**
 * Factory for resolving the appropriate AuthApplier strategy for a given AuthType.
 */
final class AuthApplierFactory
{
    /**
     * @var list<AuthApplier>
     */
    private array $appliers;

    /**
     * @param  list<AuthApplier>  $appliers
     */
    public function __construct(array $appliers = [])
    {
        $this->appliers = $appliers !== [] ? $appliers : [
            new BearerApplier,
            new BasicApplier,
            new ApiKeyApplier,
            new ActAsUserApplier,
            new SessionApplier,
        ];
    }

    public function forType(AuthType $type): ?AuthApplier
    {
        foreach ($this->appliers as $applier) {
            if ($applier->canApply(type: $type)) {
                return $applier;
            }
        }

        return null;
    }

    public function apply(RelayRequest $request): RelayRequest
    {
        $applier = $this->forType(type: $request->authType);

        if ($applier === null) {
            return $request;
        }

        return $applier->apply(request: $request);
    }
}
