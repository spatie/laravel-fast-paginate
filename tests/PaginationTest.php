<?php

use Illuminate\Database\Eloquent\Builder;
use Spatie\FastPaginate\Tests\TestSupport\NotificationWithStringKey;
use Spatie\FastPaginate\Tests\TestSupport\User;
use Spatie\FastPaginate\Tests\TestSupport\UserCollection;
use Spatie\FastPaginate\Tests\TestSupport\UserWithCustomCollection;

// These tests run against SQLite, MySQL and PostgreSQL. Instead of asserting on the SQL,
// which differs per database, they assert that the deferred join returns exactly what
// Laravel's own paginator returns.

dataset('queries', [
    'ordered by the primary key' => fn () => User::query()->orderBy('id'),
    'ordered by a column' => fn () => User::query()->orderBy('name')->orderBy('id'),
    'ordered descending' => fn () => User::query()->orderByDesc('id'),
    'with a where clause' => fn () => User::query()->where('id', '>', 4)->orderBy('id'),
    'with a limited set of columns' => fn () => User::query()->select(['id', 'name'])->orderBy('id'),
    'with an eager load' => fn () => User::query()->with('posts')->orderBy('id'),
    'ordered by a counted relation' => fn () => User::query()->withCount('posts')->orderByDesc('posts_count')->orderBy('id'),
    'ordered by an aliased aggregate' => fn () => User::query()
        ->withSum(['posts as views_count' => fn (Builder $query) => $query->where('views', '>', 0)], 'views')
        ->orderBy('views_count')
        ->orderBy('id'),
    'grouped' => fn () => User::query()->select('name')->groupBy('name')->orderBy('name'),
    'grouped with a having clause' => fn () => User::query()
        ->select('name')
        ->groupBy('name')
        ->havingRaw('count(*) > 0')
        ->orderBy('name'),
]);

it('returns the same page as the regular paginator', function (Closure $query) {
    $fast = $query()->fastPaginate(5, page: 2);
    $regular = $query()->paginate(5, page: 2);

    expect($fast->toArray())->toEqual($regular->toArray());
})->with('queries');

it('returns the same page as the regular simple paginator', function (Closure $query) {
    $fast = $query()->simpleFastPaginate(5, page: 2);
    $regular = $query()->simplePaginate(5, page: 2);

    expect($fast->toArray())->toEqual($regular->toArray());
})->with('queries');

it('returns the same page as the regular paginator for a union', function () {
    $query = fn () => User::query()
        ->where('id', '<', 10)
        ->unionAll(User::query()->where('id', '>', 10))
        ->orderBy('id');

    expect($query()->fastPaginate(5, page: 2)->toArray())
        ->toEqual($query()->paginate(5, page: 2)->toArray());
});

it('walks over every record exactly once', function () {
    $ids = [];

    foreach (range(1, 6) as $page) {
        $ids = [...$ids, ...User::query()->orderBy('name')->fastPaginate(5, page: $page)->pluck('id')->all()];
    }

    expect($ids)->toHaveCount(TOTAL_USERS)
        ->and(array_unique($ids))->toHaveCount(TOTAL_USERS);
});

it('paginates deterministically when no order is given', function () {
    $firstPage = User::query()->fastPaginate(5)->pluck('id')->all();
    $secondPage = User::query()->fastPaginate(5, page: 2)->pluck('id')->all();

    expect($firstPage)->toBe([1, 2, 3, 4, 5])
        ->and($secondPage)->toBe([6, 7, 8, 9, 10]);
});

it('reports the correct totals', function () {
    $results = User::query()->orderBy('id')->fastPaginate(5, page: 2);

    expect($results->total())->toBe(TOTAL_USERS)
        ->and($results->lastPage())->toBe(6)
        ->and($results->currentPage())->toBe(2)
        ->and($results->hasMorePages())->toBeTrue()
        ->and($results->count())->toBe(5);
});

it('returns an empty page past the last one', function () {
    $results = User::query()->orderBy('id')->fastPaginate(5, page: 100);

    expect($results->count())->toBe(0)
        ->and($results->total())->toBe(TOTAL_USERS)
        ->and($results->hasMorePages())->toBeFalse();
});

it('skips the count query when a total is given', function () {
    $queries = $this->queriesExecutedBy(function () use (&$results) {
        $results = User::query()->orderBy('id')->fastPaginate(5, page: 1, total: 100);
    });

    expect($queries)->toHaveCount(2)
        ->and($results->total())->toBe(100);
});

it('paginates a has many relation like the regular paginator', function () {
    $user = User::query()->find(1);

    expect($user->posts()->orderBy('id')->fastPaginate(5)->toArray())
        ->toEqual($user->posts()->orderBy('id')->paginate(5)->toArray());
});

it('paginates a belongs to many relation like the regular paginator', function () {
    $user = User::query()->find(1);

    expect($user->notifications()->orderBy('notifications.id')->fastPaginate(5)->toArray())
        ->toEqual($user->notifications()->orderBy('notifications.id')->paginate(5)->toArray());
});

it('hydrates the pivot of a belongs to many relation', function () {
    $user = User::query()->find(1);

    $results = $user->notifications()->withPivot('created_at')->orderBy('notifications.id')->fastPaginate(5);

    expect($results->first()->pivot->user_id)->toEqual(1)
        ->and($results->first()->pivot->created_at)->not->toBeNull();
});

it('paginates models with a string key', function () {
    $this->seedNotificationsWithStringKeys();

    $query = fn () => NotificationWithStringKey::query()->orderBy('id');

    expect($query()->fastPaginate(5, page: 2)->toArray())
        ->toEqual($query()->paginate(5, page: 2)->toArray());
});

it('eager loads relations on the returned records', function () {
    $results = User::query()->with('posts')->orderBy('id')->fastPaginate(5);

    expect($results->first()->relationLoaded('posts'))->toBeTrue()
        ->and($results->first()->posts->pluck('name')->all())->toBe(['Post 1']);
});

it('keeps the collection class defined on the model', function () {
    $results = UserWithCustomCollection::query()->orderBy('id')->fastPaginate(5);

    expect($results->getCollection())->toBeInstanceOf(UserCollection::class);
});

it('returns every record when the page size is -1', function () {
    // A page size of -1 makes Laravel drop the limit but keep the offset. PostgreSQL is
    // the only one of the three that accepts such a query, so it's the only place
    // where we can see that we handed the query off to the regular paginator.
    if ($this->driver() !== 'pgsql') {
        $this->markTestSkipped('Only PostgreSQL supports an offset without a limit.');
    }

    $results = User::query()->orderBy('id')->fastPaginate(-1);

    expect($results->count())->toBe(TOTAL_USERS);
});
