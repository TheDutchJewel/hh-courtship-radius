# hh module template for webtrees

This GitHub template provides a consistent starting point for a webtrees 2.2 custom module.

## Included

- a valid custom module bootstrap and module class;
- gettext PO/MO loading and a separate wrapper for webtrees core translations;
- `README.md`, `CHANGELOG.md`, `version.txt`, and development documentation;
- PHP and gettext checks in GitHub Actions;
- a tag-based release workflow that creates a ZIP asset with the correct module root folder;
- branch-safe defaults and a PowerShell initializer.

## Create a module

1. Create a repository from this GitHub template.
2. Clone it locally.
3. Run:

   ```powershell
   .\Initialize-Module.ps1 -ModuleName hh_example -ClassName ExampleModule -Title "Example module"
   ```

4. Review the generated metadata, README, translations, and license.
5. Commit the initialized state before implementing features.

The module name must be the exact directory name used below `modules_v4`. The class name must be a valid PHP identifier.

## Credits

Template maintained by Hermann Hartenthaler with assistance from Codex.

## License

GPL-3.0-or-later, matching webtrees and the established hh module practice.

