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

namespace GlpiPlugin\Slamonitor;

use CommonGLPI;
use Session;

/**
 * Entry point of the plugin: menu declaration and shared display helpers.
 *
 * The class is not backed by a table; it exists to carry the plugin right and
 * to be referenced by the GLPI menu.
 */
class SlaMonitor extends CommonGLPI
{
    /**
     * Name of the right controlling the access to the report.
     *
     * @var string
     */
    public static $rightname = 'plugin_slamonitor';

    protected static $notable = true;

    public static function getTypeName($nb = 0)
    {
        return __('SLA Monitor', 'slamonitor');
    }

    public static function getMenuName()
    {
        return self::getTypeName();
    }

    public static function getIcon()
    {
        return 'ti ti-alarm';
    }

    /**
     * Root of the plugin URLs.
     */
    public static function getBaseUrl(): string
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        return $CFG_GLPI['root_doc'] . '/plugins/slamonitor';
    }

    /**
     * Entry added to the "Assistance" menu.
     *
     * @return array<string,mixed>|false
     */
    public static function getMenuContent()
    {
        if (!Session::haveRight(self::$rightname, READ)) {
            return false;
        }

        $base = self::getBaseUrl();

        $menu = [
            'title' => self::getMenuName(),
            'page'  => $base . '/front/status.php',
            'icon'  => self::getIcon(),
        ];

        $menu['links']['search'] = $base . '/front/status.php';

        if (Session::haveRight(self::$rightname, UPDATE)) {
            $menu['links']['config'] = $base . '/front/config.form.php';
        }

        return $menu;
    }

    /**
     * Human readable label for a breach margin expressed in seconds.
     *
     * A negative margin means the resolution target is already missed.
     */
    public static function formatMargin(?int $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }

        $late   = $seconds < 0;
        $amount = abs($seconds);

        $days    = intdiv($amount, 86400);
        $hours   = intdiv($amount % 86400, 3600);
        $minutes = intdiv($amount % 3600, 60);

        if ($days > 0) {
            $label = sprintf('%dd %02dh', $days, $hours);
        } elseif ($hours > 0) {
            $label = sprintf('%dh %02dm', $hours, $minutes);
        } else {
            $label = sprintf('%dm', $minutes);
        }

        return $late ? '-' . $label : $label;
    }

    /**
     * Bootstrap contextual class used to colour a margin cell.
     */
    public static function marginSeverity(?int $seconds, int $warn_threshold): string
    {
        if ($seconds === null) {
            return 'secondary';
        }
        if ($seconds < 0) {
            return 'danger';
        }
        if ($seconds < $warn_threshold) {
            return 'warning';
        }

        return 'success';
    }
}
