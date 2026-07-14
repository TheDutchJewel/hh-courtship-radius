<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Service;

use Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Model\CourtshipObservation;

final class ReportService
{
    public function __construct(
        private readonly StatisticsService $statisticsService,
        private readonly TimeSliceService $timeSliceService,
    ) {
    }

    /**
     * @param array<CourtshipObservation> $observations
     * @param array<float> $percentiles
     * @return array<string,mixed>
     */
    public function build(array $observations, int $fromYear, int $toYear, array $percentiles, string $sort): array
    {
        $plan = $this->timeSliceService->plan($fromYear, $toYear);
        $filtered = array_values(array_filter(
            $observations,
            static fn (CourtshipObservation $observation): bool => $observation->marriageYear >= $fromYear
                && $observation->marriageYear <= $toYear,
        ));

        $series = [];
        foreach ($plan['slices'] as $slice) {
            $row = $slice;
            foreach (['M', 'F'] as $sex) {
                $distances = [];
                foreach ($filtered as $observation) {
                    if ($observation->sex === $sex
                        && $observation->marriageYear >= $slice['from']
                        && $observation->marriageYear <= $slice['to']) {
                        $distances[] = $observation->distance;
                    }
                }
                $row[$sex] = $this->statisticsService->summarize($distances, $percentiles);
            }
            $series[] = $row;
        }

        $totals = [];
        $histograms = [];
        $crossTables = [];
        foreach (['M', 'F'] as $sex) {
            $sexObservations = array_values(array_filter(
                $filtered,
                static fn (CourtshipObservation $observation): bool => $observation->sex === $sex,
            ));
            $distances = array_map(static fn (CourtshipObservation $observation): float => $observation->distance, $sexObservations);
            $totals[$sex] = $this->statisticsService->summarize($distances, $percentiles);
            $histograms[$sex] = $this->statisticsService->histogram($distances);
            $crossTables[$sex] = $this->crossTable($sexObservations, $sort);
        }

        $map = $this->mapData($filtered);
        $map['radii'] = [
            'M' => $totals['M']['percentiles']['90'],
            'F' => $totals['F']['percentiles']['90'],
        ];

        return [
            'plan'                => $plan,
            'observations'        => $filtered,
            'series'              => $series,
            'totals'              => $totals,
            'histograms'          => $histograms,
            'cross_tables'        => $crossTables,
            'map'                 => $map,
            'blood_relationships' => $this->bloodRelationships($filtered),
        ];
    }

    /**
     * @param array<CourtshipObservation> $observations
     * @return array{rows:array<string>,columns:array<string>,cells:array<string,array<string,int>>,row_totals:array<string,int>,column_totals:array<string,int>}
     */
    private function crossTable(array $observations, string $sort): array
    {
        $cells = [];
        $rowTotals = [];
        $columnTotals = [];
        foreach ($observations as $observation) {
            $row = $observation->origin->name;
            $column = $observation->destination->name;
            $cells[$row][$column] = ($cells[$row][$column] ?? 0) + 1;
            $rowTotals[$row] = ($rowTotals[$row] ?? 0) + 1;
            $columnTotals[$column] = ($columnTotals[$column] ?? 0) + 1;
        }

        $rows = array_keys($rowTotals);
        $columns = array_keys($columnTotals);
        if ($sort === 'frequency') {
            usort($rows, static fn (string $left, string $right): int => $rowTotals[$right] <=> $rowTotals[$left] ?: strnatcasecmp($left, $right));
            usort($columns, static fn (string $left, string $right): int => $columnTotals[$right] <=> $columnTotals[$left] ?: strnatcasecmp($left, $right));
        } else {
            natcasesort($rows);
            natcasesort($columns);
            $rows = array_values($rows);
            $columns = array_values($columns);
        }

        return compact('rows', 'columns', 'cells', 'rowTotals', 'columnTotals');
    }

    /** @param array<CourtshipObservation> $observations */
    private function mapData(array $observations): array
    {
        $routes = [];
        $samePlace = [];
        foreach ($observations as $observation) {
            $origin = $observation->origin;
            $destination = $observation->destination;
            $key = implode('|', [
                $observation->sex,
                number_format($origin->latitude, 6, '.', ''),
                number_format($origin->longitude, 6, '.', ''),
                number_format($destination->latitude, 6, '.', ''),
                number_format($destination->longitude, 6, '.', ''),
            ]);

            if ($observation->distance < 0.001) {
                if (!isset($samePlace[$key])) {
                    $samePlace[$key] = [
                        'sex' => $observation->sex,
                        'name' => $origin->name,
                        'latitude' => $origin->latitude,
                        'longitude' => $origin->longitude,
                        'count' => 0,
                    ];
                }
                $samePlace[$key]['count']++;
                continue;
            }

            if (!isset($routes[$key])) {
                $routes[$key] = [
                    'sex' => $observation->sex,
                    'origin' => $origin->toArray(),
                    'destination' => $destination->toArray(),
                    'count' => 0,
                ];
            }
            $routes[$key]['count']++;
        }

        return ['routes' => array_values($routes), 'same_place' => array_values($samePlace)];
    }

    /** @param array<CourtshipObservation> $observations */
    private function bloodRelationships(array $observations): array
    {
        $relationships = [];
        foreach ($observations as $observation) {
            if ($observation->bloodRelationship !== null && !isset($relationships[$observation->familyXref])) {
                $relationships[$observation->familyXref] = [
                    'family_xref' => $observation->familyXref,
                    'first_name'  => $observation->subjectName,
                    'second_name' => $observation->partnerName,
                    'relationship'=> $observation->bloodRelationship,
                ];
            }
        }

        return array_values($relationships);
    }
}
