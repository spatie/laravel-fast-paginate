<?php

namespace Spatie\FastPaginate\Tests\TestSupport;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'users';

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(Notification::class);
    }
}
