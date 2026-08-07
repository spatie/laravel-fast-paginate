<?php

use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Spatie\FastPaginate\Tests\TestSupport\NotificationWithStringKey;
use Spatie\FastPaginate\Tests\TestSupport\User;
use Spatie\FastPaginate\Tests\TestSupport\UserWithCustomPerPage;
use Spatie\FastPaginate\Tests\TestSupport\UserWithCustomTable;
use Spatie\FastPaginate\Tests\TestSupport\UserWithMutatedId;

it('paginates using a deferred join', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->fastPaginate();
    });

    expect($results)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($results->count())->toBe(15)
        ->and($results->last()->name)->toBe('Person 15')
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(TOTAL_USERS);

    expect($queries)->toHaveCount(3);

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by "users"."id" asc limit 16 offset 0'
    );
});

it('can use a different page size', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->fastPaginate(5);
    });

    expect($results->count())->toBe(5)
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(TOTAL_USERS);

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2, 3, 4, 5) order by "users"."id" asc limit 6 offset 0'
    );
});

it('can paginate to another page', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->fastPaginate(5, ['*'], 'page', 2);
    });

    expect($results->count())->toBe(5)
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(2)
        ->and($results->total())->toBe(TOTAL_USERS);

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (6, 7, 8, 9, 10) order by "users"."id" asc limit 6 offset 0'
    );
});

it('ignores mutations on the primary key', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = UserWithMutatedId::query()->fastPaginate(5);
    });

    expect($results->count())->toBe(5);

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2, 3, 4, 5) order by "users"."id" asc limit 6 offset 0'
    );
});

it('respects the per page defined on the model', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = UserWithCustomPerPage::query()->fastPaginate();
    });

    expect($results->count())->toBe(2)
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(TOTAL_USERS);

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2) order by "users"."id" asc limit 3 offset 0'
    );
});

it('returns the same paginator for a page that does not exist', function () {
    $existingPage = User::query()->fastPaginate();

    $queries = $this->queriesExecutedBy(function () use (&$missingPage) {
        $missingPage = User::query()->fastPaginate(2, ['*'], 'page', 16);
    });

    expect($missingPage)->toBeInstanceOf($existingPage::class)
        ->and($missingPage->count())->toBe(0)
        ->and($missingPage->hasMorePages())->toBeFalse();

    // The outer query is never executed, since there are no keys to fetch.
    expect($queries)->toHaveCount(2);
});

it('respects the table defined on the model', function () {
    UserWithCustomTable::query()->fastPaginate();
})->throws(QueryException::class, 'custom_table');

it('orders by the primary key when no order is given', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->fastPaginate(5);
    });

    expect($queries[1]['query'])->toBe(
        'select "users"."id" from "users" order by "users"."id" asc limit 5 offset 0'
    );

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2, 3, 4, 5) order by "users"."id" asc limit 6 offset 0'
    );
});

it('propagates the order to both queries', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->orderBy('name')->fastPaginate(5);
    });

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 10, 11, 12, 13) order by "name" asc limit 6 offset 0'
    );
});

it('propagates a raw order to both queries', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->orderByRaw('id % 2')->orderBy('id')->fastPaginate(5);
    });

    expect($queries[2]['query'])->toBe(
        'select * from "users" where "users"."id" in (2, 4, 6, 8, 10) order by id % 2, "id" asc limit 6 offset 0'
    );
});

it('clears eager loads on the inner query', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->with('posts')->fastPaginate(5);
    });

    // Without clearing the eager loads there would be five queries.
    expect($queries)->toHaveCount(4);

    // The eager load comes last, after the outer query has run.
    expect($queries[3]['query'])->toBe(
        'select * from "posts" where "posts"."user_id" in (1, 2, 3, 4, 5)'
    );
});

it('replaces the selects on the inner query', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->selectRaw('(select 1 as complicated_subquery)')->fastPaginate();
    });

    // Dropped for the inner query.
    expect($queries[1]['query'])->toBe(
        'select "users"."id" from "users" order by "users"."id" asc limit 15 offset 0'
    );

    // Restored for the outer query.
    expect($queries[2]['query'])->toBe(
        'select (select 1 as complicated_subquery) from "users" where "users"."id" in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by "users"."id" asc limit 16 offset 0'
    );
});

it('keeps unquoted selects that are used in the order', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()
            ->selectRaw('(select 1) as computed_column')
            ->orderBy('computed_column')
            ->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "users"."id", (select 1) as computed_column from "users" order by "computed_column" asc limit 15 offset 0'
    );
});

it('supports expressions in the order', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()
            ->selectRaw('(select 1) as computed_column')
            ->orderBy(User::query()->select('name')->orderBy('name')->limit(1)->getQuery())
            ->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "users"."id" from "users" order by (select "name" from "users" order by "name" asc limit 1) asc limit 15 offset 0'
    );
});

it('falls back to the regular paginator when the query has a having clause', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()
            ->selectRaw('count(*) as user_count')
            ->having('user_count', '>', 0)
            ->fastPaginate();
    });

    expect($queries)->toHaveCount(2);

    expect($queries[1]['query'])->toBe(
        'select count(*) as user_count from "users" having "user_count" > ? limit 15 offset 0'
    );
});

it('falls back to the regular paginator when the query has a group by clause', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->select(['name'])->groupBy('name')->fastPaginate();
    });

    expect($queries)->toHaveCount(2);

    expect($queries[1]['query'])->toBe(
        'select "name" from "users" group by "name" limit 15 offset 0'
    );
});

it('falls back to the regular paginator when the query has a union', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()
            ->where('id', '<', 10)
            ->unionAll(User::query()->where('id', '>', 10))
            ->fastPaginate(2);
    });

    expect($queries)->toHaveCount(2);
});

// A per page of -1 is a common trick to fetch all records, which defeats the purpose of
// a deferred join. SQLite can't run the offset without a limit query that the regular
// paginator builds for it, so the error proves that we handed the query off as is.
it('falls back to the regular paginator when all records are requested', function () {
    User::query()->fastPaginate(-1);
})->throws(QueryException::class, 'select * from "users" offset 0');

it('keeps a select that is used in the order', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->withCount('posts')->orderByDesc('posts_count')->fastPaginate();
    });

    expect($queries)->toHaveCount(3)
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(1)
        ->and($results->total())->toBe(TOTAL_USERS);

    expect($queries[1]['query'])->toBe(
        'select "users"."id", (select count(*) from "posts" where "users"."id" = "posts"."user_id") as "posts_count" from "users" order by "posts_count" desc limit 15 offset 0'
    );
});

it('keeps an aliased select that is used in the order', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->withCount('posts as posts_ct')->orderByDesc('posts_ct')->fastPaginate();
    });

    expect($queries)->toHaveCount(3);

    expect($queries[1]['query'])->toBe(
        'select "users"."id", (select count(*) from "posts" where "users"."id" = "posts"."user_id") as "posts_ct" from "users" order by "posts_ct" desc limit 15 offset 0'
    );
});

it('drops a select that is not used in the order', function () {
    $queries = $this->queriesExecutedBy(function () {
        User::query()->withCount('posts')->orderByDesc('id')->fastPaginate();
    });

    expect($queries)->toHaveCount(3);

    expect($queries[1]['query'])->toBe(
        'select "users"."id" from "users" order by "id" desc limit 15 offset 0'
    );
});

it('binds string keys correctly', function () {
    $this->seedNotificationsWithStringKeys();

    $queries = $this->queriesExecutedBy(function () {
        NotificationWithStringKey::query()->fastPaginate();
    });

    expect($queries)->toHaveCount(3);

    expect($queries[2]['query'])->toBe(
        'select * from "notifications" where "notifications"."id" in (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) order by "notifications"."id" asc limit 16 offset 0'
    );

    expect($queries[2]['bindings'])->toHaveCount(15)
        ->and($queries[2]['bindings'][0])->toBe('64bf6df6-06d7-11ed-b939-000000000001');
});

it('produces the same result as the regular paginator for queries with bindings in a select', function () {
    $queries = $this->queriesExecutedBy(function () use (&$fast, &$regular) {
        $fast = User::query()
            ->withSum(['posts as views_count' => fn ($query) => $query->where('views', '>', 0)], 'views')
            ->orderBy('views_count')
            ->fastPaginate();

        $regular = User::query()
            ->withSum(['posts as views_count' => fn ($query) => $query->where('views', '>', 0)], 'views')
            ->orderBy('views_count')
            ->paginate();
    });

    expect($queries[0]['query'])->toBe($queries[2]['query'])
        ->and($queries[0]['bindings'])->toBe($queries[2]['bindings'])
        ->and($queries[1]['query'])->toBe($queries[3]['query'])
        ->and($queries[1]['bindings'])->toBe($queries[3]['bindings']);

    expect($fast->toArray())->toBe($regular->toArray())
        ->and($fast)->toBeInstanceOf($regular::class);
});

it('can simple paginate', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->simpleFastPaginate();
    });

    expect($results)->toBeInstanceOf(Paginator::class)
        ->and($results->count())->toBe(15)
        ->and($results->last()->name)->toBe('Person 15')
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(1);

    expect($queries)->toHaveCount(2);

    expect($queries[1]['query'])->toBe(
        'select * from "users" where "users"."id" in (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15) order by "users"."id" asc limit 16 offset 0'
    );
});

it('can simple paginate to another page', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->simpleFastPaginate(5, ['*'], 'page', 2);
    });

    expect($results)->toBeInstanceOf(Paginator::class)
        ->and($results->count())->toBe(5)
        ->and($results->last()->name)->toBe('Person 10')
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->currentPage())->toBe(2);

    expect($queries)->toHaveCount(2);

    expect($queries[1]['query'])->toBe(
        'select * from "users" where "users"."id" in (6, 7, 8, 9, 10) order by "users"."id" asc limit 6 offset 0'
    );
});
