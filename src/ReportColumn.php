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
     * Known columns: key => SQL expression.
     *
     * @var array<string,string>
     */
    private const COLUMNS = [
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
     * Every column the report knows about.
     *
     * @return array<string,string> key => SQL expression
     */
    public static function all(): array
    {
        return self::COLUMNS;
    }

    /**
     * Keys of the columns rendered in the table, in display order.
     *
     * @return string[]
     */
    public static function displayed(): array
    {
        return self::DISPLAYED;
    }

    /**
     * Label of a column.
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
        if (!isset(self::COLUMNS[$key])) {
            throw new InvalidArgumentException(
                sprintf('Unknown report column "%s"', $key)
            );
        }

        return self::COLUMNS[$key];
    }

    /**
     * SQL expression to sort on for a given column key.
     *
     * Unknown keys fall back on the default sort rather than reaching the
     * query, so a hand-edited URL cannot influence the ORDER BY clause.
     */
    public static function sortExpression(string $key): string
    {
        return self::COLUMNS[$key] ?? self::COLUMNS[self::DEFAULT_SORT];
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
