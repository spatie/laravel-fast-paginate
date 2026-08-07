<?php

namespace Spatie\FastPaginate\Mixins;

use Closure;
use Spatie\FastPaginate\FastPaginate;

/**
 * A thin adapter, not boilerplate. `Macroable::mixin()` registers every public and
 * protected method as a macro, and calls each one without arguments, so
 * `FastPaginate` itself can't be handed to `Builder::mixin()`.
 */
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
