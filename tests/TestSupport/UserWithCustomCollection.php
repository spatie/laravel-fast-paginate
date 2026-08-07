<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

class UserWithCustomCollection extends User
{
    public function newCollection(array $models = []): UserCollection
    {
        return new UserCollection($models);
    }
}
