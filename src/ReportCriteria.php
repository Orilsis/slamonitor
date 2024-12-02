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
 * Filters and paging of one report run, extracted from the request.
 */
final class ReportCriteria
{
    public function __construct(
        public readonly string $sort,
        public readonly string $order,
        public readonly string $search,
        public readonly ?int $status,
        public readonly int $period,
        public readonly int $entity,
        public readonly bool $breached_only,
        public readonly int $limit,
        public readonly int $offset,
    ) {
    }

    /**
     * Build the criteria from a request array ($_GET on the report page).
     *
     * @param array<string,mixed> $request
     */
    public static function fromRequest(array $request): self
    {
        $config = Config::getInstance();

        return new self(
            sort:          (string) ($request['sort'] ?? ReportColumn::DEFAULT_SORT),
            order:         (string) ($request['order'] ?? ReportColumn::DEFAULT_ORDER),
            search:        trim((string) ($request['search'] ?? '')),
            status:        isset($request['status']) && $request['status'] !== ''
                               ? (int) $request['status']
                               : null,
            period:        isset($request['period'])
                               ? max(1, min(3650, (int) $request['period']))
                               : $config->getDefaultPeriod(),
            entity:        isset($request['entity']) && $request['entity'] !== ''
                               ? (int) $request['entity']
                               : $config->getReportEntity(),
            breached_only: !empty($request['breached_only']),
            limit:         $config->getPageSize(),
            offset:        max(0, (int) ($request['start'] ?? 0)),
        );
    }

    /**
     * Rebuild the query string of the report page, overriding some parameters.
     *
     * @param array<string,mixed> $overrides
     */
    public function toQueryString(array $overrides = []): string
    {
        $params = [
            'sort'   => $this->sort,
            'order'  => $this->order,
            'search' => $this->search,
            'status' => $this->status,
            'period' => $this->period,
            'entity' => $this->entity,
            'start'  => $this->offset,
        ];

        if ($this->breached_only) {
            $params['breached_only'] = 1;
        }

        $params = array_merge($params, $overrides);
        $params = array_filter(
            $params,
            static fn($value): bool => $value !== null && $value !== ''
        );

        return http_build_query($params);
    }

    /**
     * Direction to use for a column header link: clicking the active column
     * flips the order, any other column starts ascending.
     */
    public function nextDirectionFor(string $column): string
    {
        if ($column !== $this->sort) {
            return 'ASC';
        }

        return ReportColumn::normalizeDirection($this->order) === 'ASC' ? 'DESC' : 'ASC';
    }
}
