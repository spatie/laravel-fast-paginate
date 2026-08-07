<?php

namespace Spatie\FastPaginate\Mixins;

use Closure;
use Spatie\FastPaginate\FastPaginate;

class BuilderMixin
{
    public function fastPaginate(): Closure
    {
        return (new FastPaginate)->fastPaginate();
    }

    public function simpleFastPaginate(): Closure
    {
        return (new FastPaginate)->simpleFastPaginate();
    }
}
