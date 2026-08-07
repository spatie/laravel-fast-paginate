<?php

use Spatie\FastPaginate\Tests\TestSupport\SearchableUser;

it('defers to the Scout paginator', function () {
    $queries = $this->queriesExecutedBy(function () use (&$paginated, &$fastPaginated) {
        $paginated = SearchableUser::search('Person')->paginate();
        $fastPaginated = SearchableUser::search('Person')->fastPaginate();
    });

    expect($fastPaginated->count())->toBe($paginated->count())
        ->and($queries[1]['query'])->toBe($queries[3]['query']);
});

it('defers to the simple Scout paginator', function () {
    $queries = $this->queriesExecutedBy(function () use (&$paginated, &$fastPaginated) {
        $paginated = SearchableUser::search('Person')->simplePaginate();
        $fastPaginated = SearchableUser::search('Person')->simpleFastPaginate();
    });

    expect($fastPaginated->count())->toBe($paginated->count())
        ->and($queries[1]['query'])->toBe($queries[3]['query']);
});
