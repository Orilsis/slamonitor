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
use GlpiPlugin\Slamonitor\SlaMonitor;

Session::checkRight(SlaMonitor::$rightname, UPDATE);

if (isset($_POST['update'])) {
    $config = new Config();

    $config->update([
        'id'             => 1,
        'warn_threshold' => $_POST['warn_threshold'] ?? 3600,
        'default_period' => $_POST['default_period'] ?? 30,
        'page_size'      => $_POST['page_size'] ?? 25,
        'report_entity'  => $_POST['report_entity'] ?? 0,
        'extra_columns'  => $_POST['extra_columns'] ?? '{}',
    ]);

    Html::back();
}

Html::header(
    SlaMonitor::getTypeName(),
    $_SERVER['PHP_SELF'],
    'helpdesk',
    SlaMonitor::class
);

TemplateRenderer::getInstance()->display('@slamonitor/config.html.twig', [
    'config'     => Config::getInstance(),
    'action_url' => SlaMonitor::getBaseUrl() . '/front/config.form.php',
    'report_url' => SlaMonitor::getBaseUrl() . '/front/status.php',
]);

Html::footer();
