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

/**
 * Builds and runs the SLA report query.
 *
 * The report has to be assembled by hand rather than through the GLPI criteria
 * builder: the breach margin is a TIMESTAMPDIFF over two columns, the requester
 * comes from a correlated sub-select on the actors table, and both have to be
 * available to the ORDER BY clause. None of that is expressible with
 * DBmysql::request().
 */
final class ReportQuery
{
    /**
     * Fetch one page of the report.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function rows(ReportCriteria $criteria): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $sql = 'SELECT ' . self::selectClause()
             . ' FROM glpi_tickets t'
             . self::joinClause()
             . ' WHERE ' . self::whereClause($criteria)
             . ' ORDER BY ' . ReportColumn::sortExpression($criteria->sort)
             . ' ' . ReportColumn::normalizeDirection($criteria->order)
             . ' LIMIT ' . (int) $criteria->limit
             . ' OFFSET ' . (int) $criteria->offset;

        $rows   = [];
        $result = $DB->doQuery($sql);

        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Total number of rows matching the filters, for the pager.
     */
    public static function countAll(ReportCriteria $criteria): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $sql = 'SELECT COUNT(*) AS nb'
             . ' FROM glpi_tickets t'
             . self::joinClause()
             . ' WHERE ' . self::whereClause($criteria);

        $result = $DB->doQuery($sql);
        $row    = $result->fetch_assoc();

        return (int) ($row['nb'] ?? 0);
    }

    /**
     * Columns of the report, including the two computed aliases.
     */
    private static function selectClause(): string
    {
        $columns = [
            't.id',
            't.name',
            't.status',
            't.priority',
            't.date',
            't.time_to_resolve',
            't.solvedate',
            'e.completename AS entity',
            'c.completename AS category',
            's.name AS sla',
            self::requesterExpression() . ' AS requester',
            self::marginExpression() . ' AS margin',
        ];

        return implode(', ', $columns);
    }

    /**
     * Seconds left before the resolution target is missed.
     *
     * Solved tickets are frozen at their resolution date, open ones are measured
     * against the current time, so a negative value always means "breached".
     */
    private static function marginExpression(): string
    {
        return 'TIMESTAMPDIFF(SECOND, COALESCE(t.solvedate, NOW()), t.time_to_resolve)';
    }

    /**
     * Display name of the first requester of the ticket.
     */
    private static function requesterExpression(): string
    {
        return "(SELECT TRIM(CONCAT(COALESCE(u.realname, ''), ' ', COALESCE(u.firstname, '')))
                   FROM glpi_tickets_users tu
                   JOIN glpi_users u ON u.id = tu.users_id
                  WHERE tu.tickets_id = t.id
                    AND tu.type = 1
                  ORDER BY tu.id
                  LIMIT 1)";
    }

    private static function joinClause(): string
    {
        return ' LEFT JOIN glpi_entities e ON e.id = t.entities_id'
             . ' LEFT JOIN glpi_itilcategories c ON c.id = t.itilcategories_id'
             . ' LEFT JOIN glpi_slas s ON s.id = t.slas_id_ttr';
    }

    /**
     * Filters shared by the listing and the counter.
     */
    private static function whereClause(ReportCriteria $criteria): string
    {
        /** @var \DBmysql $DB */
        global $DB;

        $where = [
            't.is_deleted = 0',
            't.slas_id_ttr > 0',
            't.time_to_resolve IS NOT NULL',
            't.entities_id = ' . (int) $criteria->entity,
            't.date >= DATE_SUB(NOW(), INTERVAL ' . (int) $criteria->period . ' DAY)',
        ];

        if ($criteria->search !== '') {
            $where[] = 't.name LIKE ' . $DB->quote('%' . $criteria->search . '%');
        }

        if ($criteria->status !== null) {
            $where[] = 't.status = ' . (int) $criteria->status;
        }

        if ($criteria->breached_only) {
            $where[] = self::marginExpression() . ' < 0';
        }

        return implode(' AND ', $where);
    }
}
