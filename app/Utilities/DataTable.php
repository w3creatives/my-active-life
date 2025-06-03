<?php
declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Collection;

class DataTable
{
    protected array $searchableColumns = [];
    private int $totalCount = 0;

    protected Collection $data;

    public function setSearchableColumns(array $columns): static
    {
        $this->searchableColumns = $columns;
        return $this;
    }

    /**
     * @param $request
     * @param $query
     * @return $this
     */
    public function query($request, $query): static
    {
        $searchTerm = $request->input('search.value');
        $orderColumn = $request->input('order.0.name');
        $orderDir = $request->input('order.0.dir');

        $searchableColumns = $this->searchableColumns;

        $query = $query
            ->where(function ($query) use ($searchableColumns, $searchTerm) {
                if ($searchableColumns && $searchTerm) {
                    foreach ($searchableColumns as $column) {
                        $query->orWhere($column, 'ILIKE', "%{$searchTerm}%");
                    }
                }
                return $query;
            });

            if ($orderColumn) {
                $query = $query->orderBy($orderColumn, $orderDir);
            }

        $this->totalCount = $query->count();

        $this->data = $query->limit($request->get('length', 10))
            ->skip($request->get('start', 0))
            ->get();

        return $this;
    }

    public function response(): array
    {
        return [$this->totalCount, $this->data];
    }
}
