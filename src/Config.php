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

use CommonDBTM;

/**
 * Plugin configuration.
 *
 * A single row is created at install time and updated from the configuration
 * form; the plugin never creates a second one.
 */
class Config extends CommonDBTM
{
    public static $rightname = 'plugin_slamonitor';

    /**
     * Row loaded once per request.
     *
     * @var self|null
     */
    private static $instance = null;

    public static function getTypeName($nb = 0)
    {
        return __('SLA Monitor setup', 'slamonitor');
    }

    /**
     * Load (once per request) the configuration row.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $config = new self();

            if (!$config->getFromDB(1)) {
                // The row is created at install time; fall back on the defaults
                // if it has been removed by hand so the report stays usable.
                $config->fields = self::getDefaults();
            }

            self::$instance = $config;
        }

        return self::$instance;
    }

    /**
     * @return array<string,mixed>
     */
    public static function getDefaults(): array
    {
        return [
            'id'             => 1,
            'warn_threshold' => 3600,
            'default_period' => 30,
            'page_size'      => 25,
            'report_entity'  => 0,
            'extra_columns'  => '{}',
        ];
    }

    /**
     * Number of rows displayed per page.
     */
    public function getPageSize(): int
    {
        return max(10, min(200, (int) ($this->fields['page_size'] ?? 25)));
    }

    /**
     * Margin, in seconds, under which a ticket is flagged as "at risk".
     */
    public function getWarnThreshold(): int
    {
        return max(0, (int) ($this->fields['warn_threshold'] ?? 3600));
    }

    /**
     * Default reporting window, in days.
     */
    public function getDefaultPeriod(): int
    {
        return max(1, min(3650, (int) ($this->fields['default_period'] ?? 30)));
    }

    /**
     * Entity the public status board reports on.
     *
     * The board has no session, so it cannot scope by the viewer's entities;
     * it shows a single, configured entity (the root entity by default).
     */
    public function getReportEntity(): int
    {
        return max(0, (int) ($this->fields['report_entity'] ?? 0));
    }

    public function prepareInputForUpdate($input)
    {
        foreach (['warn_threshold', 'default_period', 'page_size', 'report_entity'] as $field) {
            if (isset($input[$field])) {
                $input[$field] = (int) $input[$field];
            }
        }

        if (isset($input['extra_columns'])) {
            $input['extra_columns'] = self::normalizeExtraColumns((string) $input['extra_columns']);
        }

        return $input;
    }

    /**
     * Store the extra column definitions as a compact JSON object.
     *
     * Invalid JSON is rejected rather than stored, so that a typo in the
     * configuration form cannot leave the report unusable.
     */
    private static function normalizeExtraColumns(string $raw): string
    {
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return '{}';
        }

        $clean = [];
        foreach ($decoded as $key => $expression) {
            if (is_string($key) && is_string($expression) && $expression !== '') {
                $clean[$key] = $expression;
            }
        }

        return json_encode($clean, JSON_UNESCAPED_SLASHES);
    }
}
