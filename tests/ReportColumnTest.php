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
 * @copyright Copyright (C) 2023-2026 SLA Monitor contributors
 * @license   GPLv2+ https://www.gnu.org/licenses/gpl-2.0.html
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Slamonitor\Tests;

use GlpiPlugin\Slamonitor\ReportColumn;
use GlpiPlugin\Slamonitor\SlaMonitor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \GlpiPlugin\Slamonitor\ReportColumn
 */
final class ReportColumnTest extends TestCase
{
    /**
     * @dataProvider builtinColumnProvider
     */
    public function testBuiltinColumnsResolveToTheirExpression(
        string $key,
        string $expected
    ): void {
        $this->assertSame($expected, ReportColumn::sortExpression($key));
    }

    /**
     * @return array<string,array{string,string}>
     */
    public static function builtinColumnProvider(): array
    {
        return [
            'id'        => ['id', 't.id'],
            'title'     => ['ticket', 't.name'],
            'entity'    => ['entity', 'e.completename'],
            'requester' => ['requester', 'requester'],
            'priority'  => ['priority', 't.priority'],
            'margin'    => ['margin', 'margin'],
        ];
    }

    public function testDirectionIsNormalisedToOneOfTwoConstants(): void
    {
        $this->assertSame('DESC', ReportColumn::normalizeDirection('desc'));
        $this->assertSame('DESC', ReportColumn::normalizeDirection('DESC'));
        $this->assertSame('ASC', ReportColumn::normalizeDirection('asc'));
        $this->assertSame('ASC', ReportColumn::normalizeDirection(''));
        $this->assertSame('ASC', ReportColumn::normalizeDirection('; DROP TABLE glpi_tickets'));
    }

    public function testTheDefaultSortIsAKnownColumn(): void
    {
        $this->assertArrayHasKey(
            ReportColumn::DEFAULT_SORT,
            ReportColumn::labels()
        );
    }

    public function testMarginFormattingKeepsTheSign(): void
    {
        $this->assertSame('-2h 00m', SlaMonitor::formatMargin(-7200));
        $this->assertSame('2h 00m', SlaMonitor::formatMargin(7200));
        $this->assertSame('1d 00h', SlaMonitor::formatMargin(86400));
        $this->assertSame('-', SlaMonitor::formatMargin(null));
    }
}
