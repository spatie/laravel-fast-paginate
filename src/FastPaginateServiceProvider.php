<?php

namespace Spatie\FastPaginate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Laravel\Scout\Builder as ScoutBuilder;
use Spatie\FastPaginate\Mixins\BuilderMixin;
use Spatie\FastPaginate\Mixins\RelationMixin;
use Spatie\FastPaginate\Mixins\ScoutMixin;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FastPaginateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-fast-paginate');
    }

    public function packageBooted(): void
    {
        Builder::mixin(new BuilderMixin);
        Relation::mixin(new RelationMixin);

        if (class_exists(ScoutBuilder::class)) {
            ScoutBuilder::mixin(new ScoutMixin);
        }
    }
}
