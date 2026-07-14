<?php

declare(strict_types=1);

namespace Hartenthaler\Webtrees\Module\HhModuleTemplate;

use Fisharebest\Localization\Translation;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;

use function file_exists;

class HhModuleTemplate extends AbstractModule implements ModuleCustomInterface
{
    use ModuleCustomTrait;

    private const MODULE_NAME = 'hh-module-template';
    private const GITHUB_USER = 'hartenthaler';

    public function title(): string
    {
        return I18N::translate('HH module template');
    }

    public function description(): string
    {
        return I18N::translate('A reusable starting point for a webtrees custom module.');
    }

    public function customModuleAuthorName(): string
    {
        return 'Hermann Hartenthaler';
    }

    public function customModuleVersion(): string
    {
        return trim((string) file_get_contents(__DIR__ . '/../version.txt'));
    }

    public function customModuleLatestVersionUrl(): string
    {
        return 'https://raw.githubusercontent.com/' . self::GITHUB_USER . '/' . self::MODULE_NAME . '/main/version.txt';
    }

    public function customModuleSupportUrl(): string
    {
        return 'https://github.com/' . self::GITHUB_USER . '/' . self::MODULE_NAME;
    }

    public function resourcesFolder(): string
    {
        return __DIR__ . '/../resources/';
    }

    public function customTranslations(string $language): array
    {
        $file = $this->resourcesFolder() . 'lang/' . $language . '.mo';

        return file_exists($file) ? (new Translation($file))->asArray() : [];
    }
}

