<?php

declare(strict_types=1);

namespace Isaacjuwon\Probe\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Isaacjuwon\Probe\Data\RelayResult;

/**
 * @property-read RelayResult $resource
 */
final class RelayResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
