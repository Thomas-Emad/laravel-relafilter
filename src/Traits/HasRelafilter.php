<?php

namespace Tes\LaravelRelafilter\Traits;

use Carbon\Carbon;

trait HasRelafilter
{
    /**
     * Apply a set of filters to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters The filters to apply. Each key is the column to filter, and the value is the value to match.
     *                       The column can be a dotted path to a related model, and the value can be a comma-separated
     *                       list of column name, operator, and value. If the operator is not provided, LIKE is assumed.
     *                       If the value is not provided, an exact match is assumed.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, array $filters)
    {
        $flatFilters = $this->flattenFilters($filters);

        collect($flatFilters)->except('start', 'end')->each(function ($filter, $column) use ($query) {
            $query->when($filter, function ($query) use ($filter, $column) {
                if (str_contains($column, '.')) {
                    $segments       = explode('.', $column);
                    $relationColumn = array_pop($segments);
                    $relationPath   = implode('.', $segments);
                    $relationColumn = explode(':', $relationColumn, 2);

                    $query->whereHas($relationPath, function ($q) use ($filter, $relationColumn) {
                        $q->where(...$this->checkFromSeparator($relationColumn[1] ?? 'LIKE', $relationColumn[0], $filter));
                    });
                } else {
                    $column = explode(':', $column, 2);

                    $query->where(...$this->checkFromSeparator($column[1] ?? 'LIKE', $column[0], $filter));
                }
            });
        });

        if (isset($flatFilters['start']) && isset($flatFilters['end'])) {
            $query->whereBetween('start_date', [
                $flatFilters['start'],
                Carbon::parse($flatFilters['end'])->endOfDay(),
            ]);
        }
    }

    /**
     * Recursively flattens a multi-dimensional array of filters into a single-dimensional associative array.
     * Each key in the resulting array is a concatenation of its parent keys.
     *
     * @param array $filters The multi-dimensional array of filters to flatten.
     * @param string $prefix The prefix to append to each key, used for recursive calls.
     * @return array A single-dimensional associative array with concatenated keys.
     */
    private function flattenFilters(array $filters, string $prefix = ''): array
    {
        $result = [];

        foreach ($filters as $key => $value) {
            $fullKey = $prefix === '' ? $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $result += $this->flattenFilters($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Checks if the given separator is one of the valid comparison operators and
     * returns the column, separator and value as an array.
     *
     * @param string $separator
     * @param string $column
     * @param mixed $value
     * @return array
     */
    private function checkFromSeparator($separator, $column, $value)
    {
        if (in_array($separator, ['=', '>', '<', '>=', '<=', '!='])) {
            return [$column, $separator, $value];
        }
        return [$column, 'LIKE', "%{$value}%"];
    }
}
