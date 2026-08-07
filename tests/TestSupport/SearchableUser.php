<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class SearchableUser extends Model
{
    use Searchable;

    protected $table = 'users';

    public function toSearchableArray(): array
    {
        return $this->toArray();
    }
}
