<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

class UserWithMutatedId extends User
{
    public function getIdAttribute($value): string
    {
        return "id:{$value}";
    }
}
