<?php

namespace Spatie\FastPaginate\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationMixin
{
    public function fastPaginate(): Closure
    {
        return $this->paginateVia('fastPaginate');
    }

    public function simpleFastPaginate(): Closure
    {
        return $this->paginateVia('simpleFastPaginate');
    }

    /**
     * Private on purpose: `Macroable::mixin()` registers every public and protected
     * method as a macro, and calls each one without arguments.
     */
    private function paginateVia(string $paginationMethod): Closure
    {
        return function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null) use ($paginationMethod) {
            /** @var Relation $this */
            if ($this instanceof HasManyThrough || $this instanceof BelongsToMany) {
                $this->query->addSelect($this->shouldSelect($columns));
            }

            $paginator = $this->query->{$paginationMethod}($perPage, $columns, $pageName, $page);

            if ($this instanceof BelongsToMany) {
                $this->hydratePivotRelation($paginator->items());
            }

            return $paginator;
        };
    }
}
