<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UserStub extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'status'];

    public $timestamps = true;

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeEmailDomain(Builder $query, string $domain): void
    {
        $query->where('email', 'like', '%@'.$domain);
    }

    /**
     * Used by tests to cover eager-loading in HasRequestQuery.
     *
     * @return HasOne<UserStub, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'id');
    }

    /**
     * @return HasMany<PostStub, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(PostStub::class, 'user_id');
    }
}
