<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\CourtshipRadiusModule\Service;

use Fisharebest\Webtrees\GedcomRecord;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Module\ModuleLanguageInterface;
use Fisharebest\Webtrees\Module\RelationshipsChartModule;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\ModuleService;
use Fisharebest\Webtrees\Services\RelationshipService;
use Illuminate\Support\Collection;
use Throwable;

final class BloodRelationshipService
{
    public function relationship(Individual $first, Individual $second): string|null
    {
        if ($first->xref() === $second->xref()) {
            return null;
        }

        return $this->vestaRelationship($first, $second)
            ?? $this->webtreesRelationship($first, $second);
    }

    private function vestaRelationship(Individual $first, Individual $second): string|null
    {
        $controllerClass = 'Cissee\\Webtrees\\Module\\ExtendedRelationships\\ExtendedRelationshipController';
        $pathClass       = 'Cissee\\WebtreesExt\\Modules\\RelationshipPath';
        $utilsClass      = 'Cissee\\WebtreesExt\\Modules\\RelationshipUtils';

        if (!class_exists($controllerClass) || !class_exists($pathClass) || !class_exists($utilsClass)) {
            return null;
        }

        try {
            $controller = new $controllerClass();
            // Mode 1 searches the whole tree for the nearest common ancestor.
            $paths = $controller->calculateRelationships_123456($first, $second, 1, 1);
            foreach ($paths as $path) {
                $relationshipPath = $pathClass::create($first->tree(), $path);
                if ($relationshipPath !== null) {
                    $name = trim((string) $utilsClass::getRelationshipName($relationshipPath));
                    if ($name !== '') {
                        return $name;
                    }
                }
            }
        } catch (Throwable) {
            // Vesta is optional. An incompatible version must not break the chart.
        }

        return null;
    }

    private function webtreesRelationship(Individual $first, Individual $second): string|null
    {
        try {
            $chart = Registry::container()->get(RelationshipsChartModule::class);
            $calculate = function (Individual $individual1, Individual $individual2): array {
                return $this->calculateRelationships($individual1, $individual2, 0, true);
            };
            $paths = $calculate->call($chart, $first, $second);

            $language = Registry::container()->get(ModuleService::class)
                ->findByInterface(ModuleLanguageInterface::class, true)
                ->first(static fn (ModuleLanguageInterface $module): bool => $module->locale()->languageTag() === I18N::languageTag());

            if (!$language instanceof ModuleLanguageInterface) {
                return null;
            }

            foreach ($paths as $path) {
                $nodes = Collection::make($path)->map(static function (string $xref, int $key) use ($first): GedcomRecord|null {
                    return $key % 2 === 0
                        ? Registry::individualFactory()->make($xref, $first->tree())
                        : Registry::familyFactory()->make($xref, $first->tree());
                });

                if ($nodes->contains(null)) {
                    continue;
                }

                $visible = true;
                foreach ($nodes as $node) {
                    if (!$node instanceof GedcomRecord || !$node->canShow()) {
                        $visible = false;
                        break;
                    }
                }
                if (!$visible) {
                    continue;
                }

                $relationshipService = Registry::container()->get(RelationshipService::class);
                $name = trim($relationshipService->nameFromPath($nodes->all(), $language));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (Throwable) {
            // Relationship information is supplementary and must remain optional.
        }

        return null;
    }
}
