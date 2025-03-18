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

use GlpiPlugin\Slamonitor\Config;
use GlpiPlugin\Slamonitor\SlaMonitor;

/*
 * Public per-SLA compliance export, consumed by the monitoring dashboard on the
 * ops network (registered as NO_CHECK in plugin_init_slamonitor()). No session.
 */

/** @var DBmysql $DB */
global $DB;

// The export is a flat dump of the breach counters per SLA, so it does not go
// through ReportQuery: no paging, no computed sort, one row per SLA.
$period = isset($_GET['period']) ? max(1, min(3650, (int) $_GET['period'])) : 30;
$entity = isset($_GET['entity']) && $_GET['entity'] !== ''
    ? (int) $_GET['entity']
    : Config::getInstance()->getReportEntity();

$where = [
    't.is_deleted = 0',
    't.slas_id_ttr > 0',
    't.time_to_resolve IS NOT NULL',
    't.date >= DATE_SUB(NOW(), INTERVAL ' . $period . ' DAY)',
    't.entities_id = ' . $entity,
];

$sql = 'SELECT s.name AS sla,'
     . ' COUNT(*) AS total,'
     . ' SUM(TIMESTAMPDIFF(SECOND, COALESCE(t.solvedate, NOW()), t.time_to_resolve) < 0) AS breached'
     . ' FROM glpi_tickets t'
     . ' INNER JOIN glpi_slas s ON s.id = t.slas_id_ttr'
     . ' WHERE ' . implode(' AND ', $where)
     . ' GROUP BY s.id, s.name'
     . ' ORDER BY s.name ASC';

$result = $DB->doQuery($sql);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="slamonitor-' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['SLA', 'Tickets', 'Breached', 'Compliance %'], ';');

while ($row = $result->fetch_assoc()) {
    $total    = (int) $row['total'];
    $breached = (int) $row['breached'];
    $rate     = $total > 0 ? round(100 * ($total - $breached) / $total, 1) : 0.0;

    fputcsv($out, [$row['sla'], $total, $breached, $rate], ';');
}

fclose($out);
