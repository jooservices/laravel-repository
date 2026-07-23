<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostStub extends Model
{
    protected $table = 'posts';

    protected $fillable = ['user_id', 'author_id', 'title', 'status', 'votes'];

    public $timestamps = true;

    /**
     * @return BelongsTo<UserStub, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(UserStub::class, 'user_id');
    }

    /**
     * Custom foreign key BelongsTo for projection regression coverage.
     *
     * @return BelongsTo<UserStub, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserStub::class, 'author_id');
    }
}
