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
        foreach (range(1, 29) as $i) {
            DB::table('users')->insert([
                'id' => $i,
                'name' => "Person {$i}",
            ]);

            DB::table('posts')->insert([
                'name' => "Post {$i}",
                'user_id' => $i,
                'views' => 1,
            ]);

            DB::table('notifications')->insert([
                'id' => $i,
                'message' => str_repeat('a', $i),
            ]);

            DB::table('notification_user')->insert([
                'notification_id' => $i,
                'user_id' => $i,
                'created_at' => now()->addSeconds($i),
                'updated_at' => now()->addSeconds($i),
            ]);
        }
    }

    protected function seedNotificationsWithStringKeys(): void
    {
        Schema::dropIfExists('notifications');

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('body');
        });

        foreach (range(1, 29) as $i) {
            DB::table('notifications')->insert([
                'id' => sprintf('64bf6df6-06d7-11ed-b939-%012d', $i),
                'body' => "Message {$i}",
            ]);
        }
    }

    protected function queriesExecutedBy(callable $callable): array
    {
        DB::enableQueryLog();

        $callable();

        DB::disableQueryLog();

        return DB::getQueryLog();
    }
}
