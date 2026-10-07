<?php

namespace App\Support\Reports;

final class ReportResult
{
    /**
     * @param  list<array{key: string, label: string}>  $columns
     * @param  list<array<string, string>>  $rows
     * @param  array<string, string>  $totals
     * @param  list<string>  $moneyKeys
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public array $totals,
        public array $moneyKeys = [],
    ) {}
}
