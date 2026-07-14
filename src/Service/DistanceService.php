<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Service;

use Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Model\PlacePoint;

final class DistanceService
{
    private const EARTH_RADIUS_KM = 6371.0088;

    public function between(PlacePoint $origin, PlacePoint $destination): float
    {
        $latitude1  = deg2rad($origin->latitude);
        $latitude2  = deg2rad($destination->latitude);
        $deltaLat   = $latitude2 - $latitude1;
        $deltaLong  = deg2rad($destination->longitude - $origin->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($latitude1) * cos($latitude2) * sin($deltaLong / 2) ** 2;

        return self::EARTH_RADIUS_KM * 2 * asin(min(1.0, sqrt($a)));
    }
}
