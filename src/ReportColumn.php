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

use InvalidArgumentException;

/**
 * Registry of the columns available in the SLA report.
 *
 * A column is identified by a short key, used both in the URL of the report and
 * in the saved report definitions, and is mapped to the SQL expression it stands
 * for. Most of them are plain columns of `glpi_tickets` or of a joined table; a
 * couple -- the breach margin and the requester name -- are aliases of
 * expressions computed by {@see ReportQuery}, because neither can be expressed
 * with the GLPI criteria builder.
 */
final class ReportColumn
{
    public const DEFAULT_SORT  = 'margin';
    public const DEFAULT_ORDER = 'ASC';

    /**
     * Built-in columns: key => SQL expression.
     *
     * @var array<string,string>
     */
    private const BUILTIN = [
        'id'        => 't.id',
        'ticket'    => 't.name',
        'entity'    => 'e.completename',
        'category'  => 'c.completename',
        'requester' => 'requester',
        'sla'       => 's.name',
        'priority'  => 't.priority',
        'status'    => 't.status',
        'opened'    => 't.date',
        'due'       => 't.time_to_resolve',
        'solved'    => 't.solvedate',
        'margin'    => 'margin',
    ];

    /**
     * Columns rendered in the table, in display order.
     *
     * @var string[]
     */
    private const DISPLAYED = [
        'id',
        'ticket',
        'entity',
        'requester',
        'sla',
        'priority',
        'due',
        'margin',
        'status',
    ];

    /**
     * Translated labels, resolved lazily so the locale is loaded.
     *
     * @return array<string,string>
     */
    public static function labels(): array
    {
        return [
            'id'        => __('ID'),
            'ticket'    => __('Title'),
            'entity'    => __('Entity'),
            'category'  => __('Category'),
            'requester' => __('Requester'),
            'sla'       => __('SLA', 'slamonitor'),
            'priority'  => __('Priority'),
            'status'    => __('Status'),
            'opened'    => __('Opening date'),
            'due'       => __('Time to resolve'),
            'solved'    => __('Resolution date'),
            'margin'    => __('Margin', 'slamonitor'),
        ];
    }

    /**
     * Site-specific columns declared by the local administrator.
     *
     * They let an installation surface fields added by another plugin (Fields,
     * Additional Fields, ...) or by a local SQL view without having to patch
     * this plugin. The definitions live in the plugin configuration, which
     * requires the UPDATE right on the plugin to change.
     *
     * @return array<string,string> key => SQL expression
     */
    public static function custom(): array
    {
        $raw     = (string) (Config::getInstance()->fields['extra_columns'] ?? '{}');
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $columns = [];
        foreach ($decoded as $key => $expression) {
            if (is_string($key) && is_string($expression) && $expression !== '') {
                $columns[$key] = $expression;
            }
        }

        return $columns;
    }

    /**
     * Every column the report knows about.
     *
     * @return array<string,string> key => SQL expression
     */
    public static function all(): array
    {
        return self::BUILTIN + self::custom();
    }

    /**
     * Keys of the columns rendered in the table, in display order.
     *
     * @return string[]
     */
    public static function displayed(): array
    {
        return array_merge(self::DISPLAYED, array_keys(self::custom()));
    }

    /**
     * Label of a column, falling back on the raw key for the custom ones.
     */
    public static function label(string $key): string
    {
        return self::labels()[$key] ?? $key;
    }

    /**
     * SQL expression to put in the SELECT clause for a given column key.
     *
     * @throws InvalidArgumentException when the key is not a known column.
     */
    public static function selectExpression(string $key): string
    {
        $columns = self::all();

        if (!isset($columns[$key])) {
            throw new InvalidArgumentException(
                sprintf('Unknown report column "%s"', $key)
            );
        }

        return $columns[$key];
    }

    /**
     * SQL expression to sort on for a given column key.
     *
     * Built-in columns are resolved through the static map above. Any other key
     * designates one of the custom columns returned by {@see self::custom()};
     * those definitions are read from the plugin configuration, which is only
     * writable by a profile holding the UPDATE right, so the expression they
     * carry is used as provided.
     */
    public static function sortExpression(string $key): string
    {
        if (isset(self::BUILTIN[$key])) {
            return self::BUILTIN[$key];
        }

        return $key;
    }

    /**
     * Normalize the sort direction coming from the request.
     *
     * Anything that is not an explicit descending order falls back on ASC, so
     * the value interpolated in the query is always one of two constants.
     */
    public static function normalizeDirection(string $direction): string
    {
        return strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
    }
}
