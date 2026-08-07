<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

class UserWithCustomTable extends User
{
    protected $table = 'custom_table';
}
