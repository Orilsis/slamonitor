# SLA Monitor

SLA breach report for GLPI.

GLPI tells you whether a ticket *has* an SLA, but the built-in search is awkward
when what you actually want is "which tickets are about to miss their resolution
target, worst first". SLA Monitor adds a single page that answers that: every
ticket under a resolution SLA, with the remaining margin computed and sortable.

![Assistance > SLA Monitor](docs/screenshot.png)

## Features

- One list of every ticket under a resolution SLA, for the entities the current
  profile can see.
- **Breach margin** — time left before `time_to_resolve`, frozen at the
  resolution date for tickets that are already solved. Negative means breached.
- Colour coding: breached (red), below the configurable "at risk" threshold
  (orange), on track (green).
- Sort on any column, including the computed margin and the requester name.
- Filter by title, status, period and "breached only".
- CSV export of the per-SLA compliance summary.
- Site-specific columns: expose a field added by another plugin (Fields,
  Additional Fields, a local SQL view…) without patching this plugin.

## The status board

The report lives at:

```
https://<your-glpi>/plugins/slamonitor/front/status.php
```

*Assistance > SLA Monitor* links there for logged-in technicians, but the page
itself is served **anonymously** — the monitoring dashboard on the ops network
polls it on a schedule and has no GLPI account, so the plugin registers it with
GLPI's `NO_CHECK` login strategy. It only exposes aggregate SLA figures for the
reporting entity. Restrict it at the vhost level if your ops network doesn't
need it.

## Requirements

| | |
|---|---|
| GLPI | 11.0.0 → 11.0.99 |
| PHP | 8.2+ |
| Database | MySQL 8.0+ / MariaDB 10.6+ |

## Installation

```sh
cd /var/www/glpi/plugins
tar xzf slamonitor-1.4.2.tar.gz
```

> The directory **must** be named `slamonitor` — GLPI derives both the class
> autoloading namespace and the configuration table name from it.

Then either install it from *Setup > Plugins*, or from the console:

```sh
sudo -u www-data php bin/console plugin:install slamonitor
sudo -u www-data php bin/console plugin:activate slamonitor
```

## Rights

Installing registers a `plugin_slamonitor` right and grants:

| Right | Granted to |
|---|---|
| `READ` | every profile that can update tickets (Technician, Hotliner, Admin, Supervisor, Super-Admin) |
| `UPDATE` | every profile that can update the GLPI configuration |

Self-Service, Observer and Read-Only get no access by default. The right can be
adjusted per profile from *Administration > Profiles > SLA Monitor*.

## Configuration

*Assistance > SLA Monitor > Setup*, or *Setup > Plugins > SLA Monitor*:

- **"At risk" threshold** — margin, in seconds, below which a ticket is flagged
  orange. Default 3600.
- **Default period** — how far back the report looks, in days. Default 30.
- **Rows per page** — default 25.
- **Site-specific columns** — a JSON object mapping a column key to the SQL
  expression it stands for, appended to the built-in column set. The expression
  is evaluated against the report query, where `t` is `glpi_tickets`; only the
  entity, category and SLA tables are joined, so anything else needs its own
  sub-select:

  ```json
  {"location": "(SELECT completename FROM glpi_locations WHERE id = t.locations_id)"}
  ```

  Editing this requires the `UPDATE` right on the plugin.

## Uninstalling

*Setup > Plugins > SLA Monitor > Uninstall*, or:

```sh
sudo -u www-data php bin/console plugin:deactivate slamonitor
sudo -u www-data php bin/console plugin:uninstall slamonitor
```

This drops `glpi_plugin_slamonitor_configs` and removes the plugin right from
every profile. Tickets and SLAs are never modified.

## Development

```sh
composer install
composer test    # phpunit
composer stan    # phpstan, level 5
composer cs      # php-cs-fixer, dry run
```

## License

GPLv2+ — see [LICENSE](LICENSE).
