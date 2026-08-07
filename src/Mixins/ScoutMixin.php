<?php

namespace Spatie\FastPaginate\Mixins;

use Closure;
use Laravel\Scout\Builder;

class ScoutMixin
{
    public function fastPaginate(): Closure
    {
        return function ($perPage = null, $pageName = 'page', $page = null) {
            /** @var Builder $this */

            // Scout results are always fetched by key anyway, so there's
            // nothing to defer. We only offer this method so that you
            // don't have to think about which paginator to reach for.
            return $this->paginate($perPage, $pageName, $page);
        };
    }

    public function simpleFastPaginate(): Closure
    {
        return function ($perPage = null, $pageName = 'page', $page = null) {
            /** @var Builder $this */
            return $this->simplePaginate($perPage, $pageName, $page);
        };
    }
}
