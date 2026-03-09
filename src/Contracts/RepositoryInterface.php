<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Contracts;

interface RepositoryInterface extends CrudRepositoryInterface, FilterableRepositoryInterface, OrderableRepositoryInterface, RequestQueryRepositoryInterface {}
