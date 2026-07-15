<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Service;

final class TimeSliceService
{
    private const WIDTHS = [1, 2, 5, 10, 20, 25, 50, 100, 200, 250, 500, 1000, 2000, 2500, 5000];

    /**
     * @return array{requested_from:int,requested_to:int,rounded_from:int,rounded_to:int,width:int,count:int,slices:array<int,array{from:int,to:int,label:string}>}
     */
    public function plan(int $fromYear, int $toYear): array
    {
        if ($fromYear > $toYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        $requestedFrom = $fromYear;
        $requestedTo   = $toYear;
        $best = null;

        foreach (self::WIDTHS as $width) {
            $roundedFrom = (int) (floor($fromYear / $width) * $width);
            $roundedTo   = (int) (ceil(($toYear + 1) / $width) * $width - 1);
            $count       = intdiv($roundedTo - $roundedFrom + 1, $width);

            $candidate = compact('width', 'roundedFrom', 'roundedTo', 'count');
            if ($count >= 5 && $count <= 10) {
                $best = $candidate;
                break;
            }

            if ($best === null || abs($count - 8) < abs($best['count'] - 8)) {
                $best = $candidate;
            }
        }

        if ($best['count'] < 5) {
            $best['roundedTo'] = $best['roundedFrom'] + 5 * $best['width'] - 1;
            $best['count'] = 5;
        }

        $slices = [];
        for ($start = $best['roundedFrom']; $start <= $best['roundedTo']; $start += $best['width']) {
            $end = $start + $best['width'] - 1;
            $slices[] = [
                'from'  => $start,
                'to'    => $end,
                'label' => $start . '–' . $end,
            ];
        }

        return [
            'requested_from' => $requestedFrom,
            'requested_to'   => $requestedTo,
            'rounded_from'   => $best['roundedFrom'],
            'rounded_to'     => $best['roundedTo'],
            'width'          => $best['width'],
            'count'          => $best['count'],
            'slices'         => $slices,
        ];
    }
}
