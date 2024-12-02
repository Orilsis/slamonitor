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

use Glpi\Http\Firewall;
use Glpi\Plugin\Hooks;
use GlpiPlugin\Slamonitor\Profile;
use GlpiPlugin\Slamonitor\SlaMonitor;

define('PLUGIN_SLAMONITOR_VERSION', '0.9.0');

/**
 * Range of GLPI versions this release has been validated against.
 */
define('PLUGIN_SLAMONITOR_MIN_GLPI', '11.0.0');
define('PLUGIN_SLAMONITOR_MAX_GLPI', '11.0.99');

/**
 * Init the hooks of the plugin.
 *
 * Called by GLPI on every request, for every activated plugin.
 */
function plugin_init_slamonitor(): void
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    // The status board is consumed by the monitoring dashboard on the ops
    // network, which has no GLPI session, so it must be reachable without
    // authentication. This has to run for anonymous requests too, hence before
    // the getLoginUserID() guard below.
    Firewall::addPluginStrategyForLegacyScripts(
        'slamonitor',
        '#^/front/status\.php#',
        Firewall::STRATEGY_NO_CHECK
    );
    Firewall::addPluginStrategyForLegacyScripts(
        'slamonitor',
        '#^/front/status\.csv\.php#',
        Firewall::STRATEGY_NO_CHECK
    );

    // Adds the "rights" tab to the profile form, so the access to the report
    // can be granted from the UI like any other GLPI right.
    Plugin::registerClass(Profile::class, ['addtabon' => ['Profile']]);

    if (!Session::getLoginUserID()) {
        return;
    }

    if (Session::haveRight(SlaMonitor::$rightname, READ)) {
        $PLUGIN_HOOKS[Hooks::MENU_TOADD]['slamonitor'] = [
            'helpdesk' => SlaMonitor::class,
        ];
    }

    if (Session::haveRight(SlaMonitor::$rightname, UPDATE)) {
        $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['slamonitor'] = 'front/config.form.php';
    }
}

/**
 * Get the name and the version of the plugin.
 *
 * @return array<string,mixed>
 */
function plugin_version_slamonitor(): array
{
    return [
        'name'         => 'SLA Monitor',
        'version'      => PLUGIN_SLAMONITOR_VERSION,
        'author'       => 'SLA Monitor contributors',
        'license'      => 'GPLv2+',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_SLAMONITOR_MIN_GLPI,
                'max' => PLUGIN_SLAMONITOR_MAX_GLPI,
            ],
        ],
    ];
}

/**
 * Check the prerequisites before installing or activating the plugin.
 */
function plugin_slamonitor_check_prerequisites(): bool
{
    if (version_compare(GLPI_VERSION, PLUGIN_SLAMONITOR_MIN_GLPI, '<')) {
        echo sprintf('This plugin requires GLPI >= %s.', PLUGIN_SLAMONITOR_MIN_GLPI);
        return false;
    }

    return true;
}

/**
 * Check the plugin configuration.
 *
 * The configuration row is created at install time and the report falls back on
 * sane defaults if it is missing, so there is nothing that can block activation.
 *
 * @param bool $verbose Whether to display a message about the configuration status.
 */
function plugin_slamonitor_check_config($verbose = false): bool
{
    return true;
}
