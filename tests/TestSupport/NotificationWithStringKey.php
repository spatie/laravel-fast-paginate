<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;

class NotificationWithStringKey extends Model
{
    protected $table = 'notifications';

    protected $keyType = 'string';
}
