# Development

The design, data flow, and definitions of the calculated indicators are documented in [architecture.md](architecture.md).

## Working copy

Keep the canonical Git working copy below `Documents/Codex/webtrees-modules` and expose it to the local webtrees test installation through an NTFS junction. Editing through either path changes the same files.

Before starting work:

```powershell
Get-HhModuleStatus.ps1 -ShowDetails
```

Before creating a pull request:

```powershell
Test-WebtreesModule.ps1 -ModulePath .
```

## Translations

Module-specific strings belong in PO files. Compile every changed PO file to its MO counterpart. Strings already translated by webtrees should be called through `MoreI18N`, keeping them out of the module catalog.

## Releases

Keep meaningful user-facing changes in the `Next release` section of `CHANGELOG.md`. Release notes are generated from this section. Build assets from tracked files only.
