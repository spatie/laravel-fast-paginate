<?php

use Illuminate\Pagination\Paginator;
use Spatie\FastPaginate\Tests\TestSupport\User;

it('can paginate a has many relation', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user) {
        $user->posts()->fastPaginate();
    });

    expect($queries[2]['query'])->toBe(
        'select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null and "posts"."id" in (1) order by "posts"."id" asc limit 16 offset 0'
    );
});

it('can simple paginate a has many relation', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user, &$results) {
        $results = $user->posts()->simpleFastPaginate();
    });

    expect($results)->toBeInstanceOf(Paginator::class)
        ->and($results->count())->toBe(1)
        ->and($results->last()->name)->toBe('Post 1')
        ->and($results->hasMorePages())->toBeFalse()
        ->and($results->currentPage())->toBe(1);

    expect($queries[1]['query'])->toBe(
        'select * from "posts" where "posts"."user_id" = ? and "posts"."user_id" is not null and "posts"."id" in (1) order by "posts"."id" asc limit 16 offset 0'
    );
});

it('can paginate a belongs to many relation', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user) {
        $user->notifications()->orderBy('created_at')->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "notifications"."id" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? order by "created_at" asc limit 15 offset 0'
    );

    expect($queries[2]['query'])->toBe(
        'select "notifications".*, "notification_user"."user_id" as "pivot_user_id", "notification_user"."notification_id" as "pivot_notification_id" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? and "notifications"."id" in (1) order by "created_at" asc limit 16 offset 0'
    );
});

it('keeps an added select on a belongs to many relation', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user) {
        $user->notifications()
            ->orderBy('created_at')
            ->addSelect('notification_user.created_at as pivot_created_at')
            ->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "notifications"."id" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? order by "created_at" asc limit 15 offset 0'
    );

    expect($queries[2]['query'])->toBe(
        'select "notification_user"."created_at" as "pivot_created_at", "notifications".*, "notification_user"."user_id" as "pivot_user_id", "notification_user"."notification_id" as "pivot_notification_id" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? and "notifications"."id" in (1) order by "created_at" asc limit 16 offset 0'
    );
});

it('keeps an aliased select that a belongs to many relation orders by', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user) {
        $user->notifications()
            ->addSelect('notification_user.created_at as pivot_created_at')
            ->orderBy('pivot_created_at')
            ->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "notifications"."id", "notification_user"."created_at" as "pivot_created_at" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? order by "pivot_created_at" asc limit 15 offset 0'
    );

    expect($queries[2]['query'])->toBe(
        'select "notification_user"."created_at" as "pivot_created_at", "notifications".*, "notification_user"."user_id" as "pivot_user_id", "notification_user"."notification_id" as "pivot_notification_id" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? and "notifications"."id" in (1) order by "pivot_created_at" asc limit 16 offset 0'
    );
});

it('hydrates the pivot on a belongs to many relation', function () {
    $user = User::query()->first();

    $queries = $this->queriesExecutedBy(function () use ($user, &$results) {
        $results = $user->notifications()
            ->withPivot('created_at')
            ->orderBy('pivot_created_at')
            ->fastPaginate();
    });

    expect($queries[1]['query'])->toBe(
        'select "notifications"."id", "notification_user"."created_at" as "pivot_created_at" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? order by "pivot_created_at" asc limit 15 offset 0'
    );

    expect($queries[2]['query'])->toBe(
        'select "notifications".*, "notification_user"."user_id" as "pivot_user_id", "notification_user"."notification_id" as "pivot_notification_id", "notification_user"."created_at" as "pivot_created_at" from "notifications" inner join "notification_user" on "notifications"."id" = "notification_user"."notification_id" where "notification_user"."user_id" = ? and "notifications"."id" in (1) order by "pivot_created_at" asc limit 16 offset 0'
    );

    expect($results->first()->relationLoaded('pivot'))->toBeTrue();
});
