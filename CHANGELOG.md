# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.2] - 2026-05-18

### Fixed

- Pager could skip a row when the page size was changed while browsing.
- `Export summary (CSV)` no longer ignores the period filter.

## [1.4.1] - 2026-03-02

### Fixed

- Margin was computed against the current time for solved tickets, so a ticket
  resolved on time drifted into "breached" a few days later. It is now frozen at
  the resolution date.

## [1.4.0] - 2026-02-11

### Added

- Compatibility with GLPI 11.0. Dropped support for 10.0.

### Changed

- Templates migrated to Twig.
- Report page no longer bootstraps through `inc/includes.php`.

## [1.3.0] - 2025-09-30

### Added

- "Breached only" filter.
- Colour coding of the margin column, with a configurable "at risk" threshold.

## [1.2.0] - 2025-06-17

### Added

- Site-specific columns: the report column set can be extended from the plugin
  configuration, so installations using the Fields plugin can surface their own
  fields without patching this plugin.
- Requester column, resolved from the ticket actors.

### Changed

- Column definitions moved out of the query builder into a dedicated registry.

## [1.1.0] - 2025-03-24

### Added

- CSV export of the per-SLA compliance summary.
- Period filter.

### Fixed

- Entity scoping used the active entity instead of the whole visible subtree.

## [1.0.1] - 2025-01-20

### Fixed

- Install failed on MariaDB when the default collation was not `utf8mb4`.

## [1.0.0] - 2024-12-09

Initial public release.

- SLA breach report with computed margin, sorting and paging.
- Plugin right, granted to the profiles allowed to update tickets.
- Configuration form.
