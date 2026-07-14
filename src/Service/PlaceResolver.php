<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Service;

use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Location;
use Fisharebest\Webtrees\PlaceLocation;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\GedcomService;
use Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Model\PlacePoint;

final class PlaceResolver
{
    public function resolve(Fact $fact): PlacePoint|null
    {
        $placeName = trim($fact->place()->gedcomName());
        $latitude  = $fact->latitude();
        $longitude = $fact->longitude();

        if ($latitude !== null && $longitude !== null) {
            return new PlacePoint($this->placeLabel($placeName), $latitude, $longitude, 'fact');
        }

        $linked = $this->linkedLocation($fact);
        if ($linked !== null) {
            [$locationName, $latitude, $longitude] = $linked;

            return new PlacePoint($this->placeLabel($placeName !== '' ? $placeName : $locationName), $latitude, $longitude, 'location');
        }

        if ($placeName === '') {
            return null;
        }

        $location = new PlaceLocation($placeName);
        $latitude = $location->latitude();
        $longitude = $location->longitude();

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return new PlacePoint($placeName, $latitude, $longitude, 'place_directory');
    }

    private function placeLabel(string $name): string
    {
        return $name !== '' ? $name : I18N::translate('Unknown place');
    }

    /** @return array{string,float,float}|null */
    private function linkedLocation(Fact $fact): array|null
    {
        if (preg_match('/\n\d+ _LOC @([^@]+)@/', $fact->gedcom(), $match) !== 1) {
            return null;
        }

        $location = Registry::locationFactory()->make($match[1], $fact->record()->tree());
        if (!$location instanceof Location || !$location->canShow()) {
            return null;
        }

        $gedcom = $location->gedcom();
        if (preg_match('/\n\d+ LATI (.+)/', $gedcom, $latitudeMatch) !== 1
            || preg_match('/\n\d+ LONG (.+)/', $gedcom, $longitudeMatch) !== 1) {
            return null;
        }

        $gedcomService = new GedcomService();
        $latitude  = $gedcomService->readLatitude(trim($latitudeMatch[1]));
        $longitude = $gedcomService->readLongitude(trim($longitudeMatch[1]));
        if ($latitude === null || $longitude === null) {
            return null;
        }

        $name = '';
        foreach ($location->facts(['NAME']) as $nameFact) {
            $name = trim($nameFact->value());
            if ($name !== '') {
                break;
            }
        }

        return [$name, $latitude, $longitude];
    }
}
