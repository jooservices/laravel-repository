<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftUserStub extends Model
{
    use SoftDeletes;

    protected $table = 'soft_users';

    protected $fillable = ['name', 'email', 'status'];

    public $timestamps = true;
}
