<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class UserStub extends Model
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'status'];

    public $timestamps = true;

    /** Used by tests to cover eager-loading in HasRequestQuery. */
    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(self::class, 'id', 'id');
    }
}
