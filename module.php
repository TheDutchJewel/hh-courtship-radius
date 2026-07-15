<?php

declare(strict_types=1);

use Hartenthaler\Webtrees\Module\CourtshipRadiusModule\CourtshipRadiusModule;
use Fisharebest\Webtrees\Registry;
use Fisharebest\Webtrees\Services\LeafletJsService;
use Fisharebest\Webtrees\Services\ModuleService;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Hartenthaler\\Webtrees\\Module\\CourtshipRadiusModule\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

return new CourtshipRadiusModule(
    Registry::container()->get(ModuleService::class),
    Registry::container()->get(LeafletJsService::class),
);
