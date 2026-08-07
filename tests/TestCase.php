<?php

namespace Spatie\FastPaginate\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Scout\ScoutServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\FastPaginate\FastPaginateServiceProvider;

class TestCase extends Orchestra
{
    public const TOTAL_USERS = 29;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->seedRecords();
    }

    protected function getPackageProviders($app)
    {
        return [
            FastPaginateServiceProvider::class,
            ScoutServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', env('DB_CONNECTION', 'testing'));
        config()->set('scout.driver', 'collection');
    }

    protected function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    protected function createTables(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_user');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
            $table->integer('views');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->text('message');
        });

        Schema::create('notification_user', function (Blueprint $table) {
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    protected function seedRecords(): void
    {
        $numbers = range(1, static::TOTAL_USERS);
        $now = now();

        DB::table('users')->insert(array_map(fn (int $i) => [
            'id' => $i,
            'name' => "Person {$i}",
        ], $numbers));

        DB::table('posts')->insert(array_map(fn (int $i) => [
            'name' => "Post {$i}",
            'user_id' => $i,
            'views' => 1,
        ], $numbers));

        DB::table('notifications')->insert(array_map(fn (int $i) => [
            'id' => $i,
            'message' => str_repeat('a', $i),
        ], $numbers));

        DB::table('notification_user')->insert(array_map(fn (int $i) => [
            'notification_id' => $i,
            'user_id' => $i,
            'created_at' => $now->copy()->addSeconds($i),
            'updated_at' => $now->copy()->addSeconds($i),
        ], $numbers));
    }

    protected function seedNotificationsWithStringKeys(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('body');
        });

        DB::table('notifications')->insert(array_map(fn (int $i) => [
            'id' => sprintf('64bf6df6-06d7-11ed-b939-%012d', $i),
            'body' => "Message {$i}",
        ], range(1, static::TOTAL_USERS)));
    }

    protected function queriesExecutedBy(callable $callable): array
    {
        DB::enableQueryLog();

        $callable();

        DB::disableQueryLog();

        return DB::getQueryLog();
    }
}
