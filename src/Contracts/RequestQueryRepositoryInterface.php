<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

use Illuminate\Http\Request;

interface RequestQueryRepositoryInterface
{
    public function fromRequest(Request $request): static;
}
