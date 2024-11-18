<?php

/**
 * -------------------------------------------------------------------------
 * SLA Monitor plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of SLA Monitor.
 *
 * SLA Monitor is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * SLA Monitor is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @copyright Copyright (C) 2023-2026 SLA Monitor contributors
 * @license   GPLv2+ https://www.gnu.org/licenses/gpl-2.0.html
 * -------------------------------------------------------------------------
 */

use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Slamonitor\Config;
use GlpiPlugin\Slamonitor\SlaMonitor;

/**
 * Install the plugin: create the configuration table, then register the right.
 */
function plugin_slamonitor_install(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    $migration = new Migration(PLUGIN_SLAMONITOR_VERSION);
    $table     = Config::getTable();

    if (!$DB->tableExists($table)) {
        $charset   = DBConnection::getDefaultCharset();
        $collation = DBConnection::getDefaultCollation();
        $sign      = DBConnection::getDefaultPrimaryKeySignOption();

        $DB->doQuery(
            "CREATE TABLE `$table` (
                `id`             int {$sign} NOT NULL AUTO_INCREMENT,
                `warn_threshold` int NOT NULL DEFAULT '3600',
                `default_period` int NOT NULL DEFAULT '30',
                `page_size`      int NOT NULL DEFAULT '25',
                `report_entity`  int NOT NULL DEFAULT '0',
                `extra_columns`  text,
                `date_mod`       timestamp NULL DEFAULT NULL,
                `date_creation`  timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation} ROW_FORMAT=DYNAMIC;"
        );

        $config = new Config();
        $config->add([
            'warn_threshold' => 3600,
            'default_period' => 30,
            'page_size'      => 25,
            'report_entity'  => 0,
            'extra_columns'  => '{}',
        ]);
    } else {
        // Upgrade path: the status-board entity was added in 1.5.
        $migration->addField($table, 'report_entity', 'integer', [
            'value' => 0,
            'after' => 'page_size',
        ]);
    }

    // Open the report to every profile already allowed to work on tickets.
    //
    // addRight() inserts a row for *every* profile in one pass -- the granted
    // value where the required rights are met, 0 everywhere else -- and returns
    // early if any row already exists. It must therefore be called exactly once,
    // and before anything else touches glpi_profilerights for this right.
    $migration->addRight(SlaMonitor::$rightname, READ, ['ticket' => UPDATE]);

    // addRight() writes a single value, so the profiles that administer the GLPI
    // setup are upgraded to UPDATE afterwards; they are the ones allowed to edit
    // the report configuration.
    foreach (plugin_slamonitor_getConfigManagerProfiles() as $profiles_id) {
        ProfileRight::updateProfileRights(
            $profiles_id,
            [SlaMonitor::$rightname => READ | UPDATE]
        );
    }

    $migration->executeMigration();

    return true;
}

/**
 * Uninstall the plugin: drop the configuration table and remove the right.
 */
function plugin_slamonitor_uninstall(): bool
{
    /** @var DBmysql $DB */
    global $DB;

    $table = Config::getTable();

    if ($DB->tableExists($table)) {
        $DB->doQuery("DROP TABLE `$table`");
    }

    // Mandatory: Migration has no counterpart to addRight(). If these rows are
    // left behind, the next install sees them, takes addRight()'s early return
    // and grants the right to nobody.
    ProfileRight::deleteProfileRights([SlaMonitor::$rightname]);

    return true;
}

/**
 * Ids of the profiles allowed to update the GLPI configuration.
 *
 * @return int[]
 */
function plugin_slamonitor_getConfigManagerProfiles(): array
{
    /** @var DBmysql $DB */
    global $DB;

    $ids = [];

    $iterator = $DB->request([
        'SELECT' => 'profiles_id',
        'FROM'   => 'glpi_profilerights',
        'WHERE'  => [
            'name' => 'config',
            new QueryExpression(
                $DB::quoteName('rights') . ' & ' . UPDATE . ' = ' . UPDATE
            ),
        ],
    ]);

    foreach ($iterator as $row) {
        $ids[] = (int) $row['profiles_id'];
    }

    return $ids;
}
