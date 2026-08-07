<?php

namespace Spatie\FastPaginate;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Spatie\FastPaginate\Exceptions\QueryIncompatibleWithFastPagination;

class FastPaginate
{
    public function fastPaginate(): Closure
    {
        return $this->paginate('paginate', function (array $items, $paginator) {
            /** @var Builder $this */
            return $this->paginator(
                $this->model->newCollection($items),
                $paginator->total(),
                $paginator->perPage(),
                $paginator->currentPage(),
                $paginator->getOptions(),
            );
        });
    }

    public function simpleFastPaginate(): Closure
    {
        return $this->paginate('simplePaginate', function (array $items, $paginator) {
            /** @var Builder $this */
            return $this->simplePaginator(
                $this->model->newCollection($items),
                $paginator->perPage(),
                $paginator->currentPage(),
                $paginator->getOptions(),
            )->hasMorePagesWhen($paginator->hasMorePages());
        });
    }

    protected function paginate(string $paginationMethod, Closure $paginatorOutput): Closure
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null) use (
            $paginationMethod,
            $paginatorOutput,
        ) {
            /** @var Builder $this */
            $base = $this->getQuery();

            // Havings, groups, and unions don't work well with this paradigm, because
            // we are counting on each row of the inner query to return a primary key
            // that we can use. When grouping, that's not always the case.
            if (filled($base->havings) || filled($base->groups) || filled($base->unions)) {
                return $this->{$paginationMethod}($perPage, $columns, $pageName, $page, $total);
            }

            // Some databases allow for offset 0 with no limit, and some people use it
            // as a hack to get all records. Since that defeats the purpose of fast
            // pagination, we'll just return the regular paginator in that case.
            if ($perPage === -1) {
                return $this->{$paginationMethod}($perPage, $columns, $pageName, $page, $total);
            }

            try {
                $innerSelectColumns = FastPaginate::getInnerSelectColumns($this);
            } catch (QueryIncompatibleWithFastPagination) {
                return $this->{$paginationMethod}($perPage, $columns, $pageName, $page, $total);
            }

            $model = $this->newModelInstance();
            $key = $model->getKeyName();
            $table = $model->getTable();

            // Without an order, the database may return rows in an unpredictable
            // order, which would make the inner and outer query disagree about
            // which records belong on the current page.
            if (empty($base->orders)) {
                $this->orderBy("{$table}.{$key}");
            }

            $innerQuery = $this->clone()
                // Only select the primary key. We'll fetch the full records in a second query below.
                ->select($innerSelectColumns)
                // The eager loads remain on the query that actually gets the records.
                ->withoutEagerLoads();

            $paginator = $innerQuery->{$paginationMethod}($perPage, ['*'], $pageName, $page, $total);

            // Get the key values from the records on the current page without mutating them.
            $ids = $paginator->getCollection()->map->getRawOriginal($key)->toArray();

            if (count($ids) === 0) {
                return $paginator;
            }

            if (in_array($model->getKeyType(), ['int', 'integer'])) {
                $this->query->whereIntegerInRaw("{$table}.{$key}", $ids);
            } else {
                $this->query->whereIn("{$table}.{$key}", $ids);
            }

            // The $paginator is full of records that are primary keys only. Here, we create a
            // new paginator with all of the stats from the index-only paginator, but with
            // the items coming from the outer query.
            $items = $this->simplePaginate($perPage, $columns, $pageName, 1)->items();

            return Closure::fromCallable($paginatorOutput)->call($this, $items, $paginator);
        };
    }

    /**
     * @throws QueryIncompatibleWithFastPagination
     */
    public static function getInnerSelectColumns(Builder $builder): array
    {
        $base = $builder->getQuery();
        $model = $builder->newModelInstance();
        $key = $model->getKeyName();
        $table = $model->getTable();

        // Collect all of the orders off of the base query and pluck the column out.
        // Based on what orders are present, we may have to include certain
        // columns in the inner query.
        $orders = collect($base->orders)
            ->pluck('column')
            ->filter()
            ->map(function ($column) use ($base) {
                // Not everyone quotes their custom selects, which is totally
                // reasonable. We'll look for both quoted and unquoted.
                $column = $column instanceof Expression ? $column->getValue($base->grammar) : $column;

                return [$column, $base->grammar->wrap($column)];
            })
            ->flatten(1);

        return collect($base->columns)
            ->filter(function ($column) use ($orders, $base) {
                $column = $column instanceof Expression
                    ? $column->getValue($base->grammar)
                    : $base->grammar->wrap($column);

                // If we're ordering by this column, we need to keep it in the inner query.
                foreach ($orders as $order) {
                    if (str_contains($column, "as {$order}")) {
                        return true;
                    }
                }

                return false;
            })
            ->each(function ($column) use ($base) {
                if ($column instanceof Expression) {
                    $column = $column->getValue($base->grammar);
                }

                if (str_contains($column, '?')) {
                    throw new QueryIncompatibleWithFastPagination;
                }
            })
            ->prepend("{$table}.{$key}")
            ->unique()
            ->values()
            ->toArray();
    }
}
