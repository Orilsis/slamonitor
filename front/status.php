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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Slamonitor\Config;
use GlpiPlugin\Slamonitor\ReportColumn;
use GlpiPlugin\Slamonitor\ReportCriteria;
use GlpiPlugin\Slamonitor\ReportQuery;
use GlpiPlugin\Slamonitor\SlaMonitor;

/*
 * Public SLA status board.
 *
 * This page is intentionally reachable without authentication: it is polled by
 * the monitoring dashboard on the ops network, which has no GLPI account. It
 * only exposes aggregate SLA figures for the reporting entity, so it is served
 * anonymously (see plugin_init_slamonitor(), which registers it as NO_CHECK).
 */

$config   = Config::getInstance();
$criteria = ReportCriteria::fromRequest($_GET);

$rows  = ReportQuery::rows($criteria);
$total = ReportQuery::countAll($criteria);

$threshold = $config->getWarnThreshold();

// Decorate the raw rows with what the template needs to render a cell.
foreach ($rows as &$row) {
    $margin              = $row['margin'] === null ? null : (int) $row['margin'];
    $row['margin_label'] = SlaMonitor::formatMargin($margin);
    $row['margin_class'] = SlaMonitor::marginSeverity($margin, $threshold);
    $row['status_label'] = Ticket::getStatus((int) $row['status']);
    $row['priority_label'] = CommonITILObject::getPriorityName((int) $row['priority']);
}
unset($row);

$columns = [];
foreach (ReportColumn::displayed() as $key) {
    $columns[$key] = ReportColumn::label($key);
}

$base_url = SlaMonitor::getBaseUrl() . '/front/status.php';

Html::simpleHeader(SlaMonitor::getTypeName(), [
    SlaMonitor::getTypeName() => '/plugins/slamonitor/front/status.php',
]);

TemplateRenderer::getInstance()->display('@slamonitor/status.html.twig', [
    'rows'       => $rows,
    'columns'    => $columns,
    'criteria'   => $criteria,
    'total'      => $total,
    'base_url'   => $base_url,
    'export_url' => SlaMonitor::getBaseUrl() . '/front/status.csv.php',
    'statuses'   => Ticket::getAllStatusArray(),
]);

Html::helpFooter();
