<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

class UserWithCustomPerPage extends User
{
    protected $perPage = 2;
}
