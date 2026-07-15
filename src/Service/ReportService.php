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
     * @param array<int,array{family_xref:string,marriage_year:int,first_name:string,second_name:string,blood_relationship:string|null}> $marriages
     * @param array<float> $percentiles
     * @return array<string,mixed>
     */
    public function build(array $observations, array $marriages, int $fromYear, int $toYear, array $percentiles, string $sort): array
    {
        $plan = $this->timeSliceService->plan($fromYear, $toYear);
        $filtered = array_values(array_filter(
            $observations,
            static fn (CourtshipObservation $observation): bool => $observation->marriageYear >= $fromYear
                && $observation->marriageYear <= $toYear,
        ));
        $filteredMarriages = array_values(array_filter(
            $marriages,
            static fn (array $marriage): bool => $marriage['marriage_year'] >= $fromYear
                && $marriage['marriage_year'] <= $toYear,
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
        $crossTables = [];
        $distancesBySex = [];
        foreach (['M', 'F'] as $sex) {
            $sexObservations = array_values(array_filter(
                $filtered,
                static fn (CourtshipObservation $observation): bool => $observation->sex === $sex,
            ));
            $distances = array_map(static fn (CourtshipObservation $observation): float => $observation->distance, $sexObservations);
            $distancesBySex[$sex] = $distances;
            $totals[$sex] = $this->statisticsService->summarize($distances, $percentiles);
            $crossTables[$sex] = $this->crossTable($sexObservations, $sort);
        }

        $allDistances = array_merge($distancesBySex['M'], $distancesBySex['F']);
        $histogramMaximum = $allDistances === [] ? 10.0 : (float) max($allDistances);
        $histograms = [
            'M' => $this->statisticsService->histogram($distancesBySex['M'], $histogramMaximum),
            'F' => $this->statisticsService->histogram($distancesBySex['F'], $histogramMaximum),
        ];

        $map = $this->mapData($filtered);
        $map['reference_circles'] = [
            'M' => $this->referenceCircle($filtered, $series, 'M'),
            'F' => $this->referenceCircle($filtered, $series, 'F'),
        ];

        $bloodRelationships = $this->bloodRelationships($filteredMarriages);
        $marriageCount = count($filteredMarriages);
        $relatedMarriageCount = count($bloodRelationships);

        return [
            'plan'                => $plan,
            'observations'        => $filtered,
            'series'              => $series,
            'totals'              => $totals,
            'histograms'          => $histograms,
            'cross_tables'        => $crossTables,
            'map'                 => $map,
            'blood_relationships' => $bloodRelationships,
            'consanguinity'       => [
                'related_marriages' => $relatedMarriageCount,
                'total_marriages'   => $marriageCount,
                'rate'              => $marriageCount === 0 ? 0.0 : $relatedMarriageCount / $marriageCount,
            ],
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

    /**
     * @param array<CourtshipObservation>    $observations
     * @param array<int,array<string,mixed>> $series
     * @return array{center:array{name:string,latitude:float,longitude:float,coordinate_source:string},person_count:int,radius:float,time_slice_count:int}|null
     */
    private function referenceCircle(array $observations, array $series, string $sex): array|null
    {
        $p90Values = [];
        foreach ($series as $row) {
            $p90 = $row[$sex]['percentiles']['90'];
            if ($p90 !== null) {
                $p90Values[] = $p90;
            }
        }

        if ($p90Values === []) {
            return null;
        }

        $origins = [];
        foreach ($observations as $observation) {
            if ($observation->sex !== $sex) {
                continue;
            }

            $origin = $observation->origin;
            $key = number_format($origin->latitude, 6, '.', '') . '|' . number_format($origin->longitude, 6, '.', '');
            if (!isset($origins[$key])) {
                $origins[$key] = ['center' => $origin->toArray(), 'person_count' => 0];
            }
            $origins[$key]['person_count']++;
        }

        $origins = array_values($origins);
        usort($origins, static fn (array $left, array $right): int =>
            $right['person_count'] <=> $left['person_count']
            ?: strnatcasecmp($left['center']['name'], $right['center']['name']));

        if ($origins === []) {
            return null;
        }

        return [
            'center'           => $origins[0]['center'],
            'person_count'     => $origins[0]['person_count'],
            'radius'           => array_sum($p90Values) / count($p90Values),
            'time_slice_count' => count($p90Values),
        ];
    }

    /** @param array<int,array{family_xref:string,marriage_year:int,first_name:string,second_name:string,blood_relationship:string|null}> $marriages */
    private function bloodRelationships(array $marriages): array
    {
        $relationships = [];
        foreach ($marriages as $marriage) {
            if ($marriage['blood_relationship'] !== null) {
                $relationships[$marriage['family_xref']] = [
                    'family_xref'  => $marriage['family_xref'],
                    'first_name'   => $marriage['first_name'],
                    'second_name'  => $marriage['second_name'],
                    'relationship' => $marriage['blood_relationship'],
                ];
            }
        }

        return array_values($relationships);
    }
}
