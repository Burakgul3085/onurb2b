<?php

namespace App\Exports;

use App\Support\Reports\ReportResult;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TabularReportExport implements FromArray, WithHeadings
{
    public function __construct(private ReportResult $result) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return array_column($this->result->columns, 'label');
    }

    /**
     * @return list<list<string>>
     */
    public function array(): array
    {
        $rows = array_map(function (array $row) {
            return array_map(fn (array $column) => $row[$column['key']] ?? '', $this->result->columns);
        }, $this->result->rows);

        if ($this->result->totals !== []) {
            $rows[] = array_map(function (array $column, int $index) {
                if ($index === 0 && ! array_key_exists($column['key'], $this->result->totals)) {
                    return 'Toplam';
                }

                return $this->result->totals[$column['key']] ?? '';
            }, $this->result->columns, array_keys($this->result->columns));
        }

        return $rows;
    }
}
