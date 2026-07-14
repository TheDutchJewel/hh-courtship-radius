<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Model;

final class CourtshipObservation
{
    public function __construct(
        public readonly string $familyXref,
        public readonly string $subjectXref,
        public readonly string $subjectName,
        public readonly string $sex,
        public readonly string $partnerXref,
        public readonly string $partnerName,
        public readonly int $marriageYear,
        public readonly PlacePoint $origin,
        public readonly PlacePoint $destination,
        public readonly string $destinationKind,
        public readonly float $distance,
        public readonly string|null $bloodRelationship,
    ) {
    }

    /** @return array<string,float|int|string|null|array<string,float|string>> */
    public function toArray(): array
    {
        return [
            'family_xref'       => $this->familyXref,
            'subject_xref'      => $this->subjectXref,
            'subject_name'      => $this->subjectName,
            'sex'               => $this->sex,
            'partner_xref'      => $this->partnerXref,
            'partner_name'      => $this->partnerName,
            'marriage_year'     => $this->marriageYear,
            'origin'            => $this->origin->toArray(),
            'destination'       => $this->destination->toArray(),
            'destination_kind'  => $this->destinationKind,
            'distance'          => $this->distance,
            'blood_relationship' => $this->bloodRelationship,
        ];
    }
}
