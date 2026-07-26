<?php

declare(strict_types=1);

namespace JOOservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CommentStub extends Model
{
    protected $table = 'comments';

    protected $fillable = ['body', 'commentable_id', 'commentable_type'];

    public $timestamps = true;

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }
}
