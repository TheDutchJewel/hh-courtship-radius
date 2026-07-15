<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Model;

final class PlacePoint
{
    public function __construct(
        public readonly string $name,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $coordinateSource,
    ) {
    }

    /** @return array{name:string,latitude:float,longitude:float,coordinate_source:string} */
    public function toArray(): array
    {
        return [
            'name'              => $this->name,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'coordinate_source' => $this->coordinateSource,
        ];
    }
}
