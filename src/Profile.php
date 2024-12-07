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
use Html;
use ProfileRight;
use Session;

/**
 * Adds the plugin right to the GLPI profile form.
 *
 * Without this tab the right exists in the database but cannot be granted from
 * the interface.
 */
class Profile extends \Profile
{
    public static function getTypeName($nb = 0)
    {
        return SlaMonitor::getTypeName($nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof \Profile && $item->getField('id') > 0) {
            return self::createTabEntry(SlaMonitor::getTypeName());
        }

        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if ($item instanceof \Profile) {
            $profiles_id = (int) $item->getField('id');
            self::addDefaultProfileInfos($profiles_id, [SlaMonitor::$rightname => 0]);
            self::showForProfile($profiles_id);
        }

        return true;
    }

    /**
     * Make sure the profile carries a row for the plugin right before the
     * matrix is rendered, so a profile created after the plugin was installed
     * still shows the checkbox.
     *
     * @param array<string,int> $rights
     */
    public static function addDefaultProfileInfos(int $profiles_id, array $rights): void
    {
        $profile_right = new ProfileRight();

        foreach ($rights as $right => $value) {
            if (
                !countElementsInTable(
                    'glpi_profilerights',
                    ['profiles_id' => $profiles_id, 'name' => $right]
                )
            ) {
                $profile_right->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $right,
                    'rights'      => $value,
                ]);
            }
        }
    }

    /**
     * Render the rights matrix for one profile.
     */
    public static function showForProfile(int $profiles_id): void
    {
        $profile = new \Profile();
        $profile->getFromDB($profiles_id);

        $canedit = Session::haveRightsOr(
            'profile',
            [CREATE, UPDATE, PURGE]
        );

        echo "<div class='spaced'>";
        if ($canedit) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $rights = [
            [
                'itemtype' => SlaMonitor::class,
                'label'    => SlaMonitor::getTypeName(),
                'field'    => SlaMonitor::$rightname,
                'rights'   => [
                    READ   => __('Read'),
                    UPDATE => __('Update'),
                ],
            ],
        ];

        $profile->displayRightsChoiceMatrix($rights, [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => SlaMonitor::getTypeName(),
        ]);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
            echo "</div>";
            Html::closeForm();
        }
        echo "</div>";
    }
}
