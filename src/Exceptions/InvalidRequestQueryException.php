<?php

declare(strict_types=1);

namespace Jooservices\LaravelRepository\Exceptions;

class InvalidRequestQueryException extends RepositoryException
{
    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedFilter(string $column, array $allowed): self
    {
        return new self(sprintf(
            'Filter [%s] is not allowed. Allowed filters: %s.',
            $column,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedSort(string $column, array $allowed): self
    {
        return new self(sprintf(
            'Sort [%s] is not allowed. Allowed sorts: %s.',
            $column,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedInclude(string $relation, array $allowed): self
    {
        return new self(sprintf(
            'Include [%s] is not allowed. Allowed includes: %s.',
            $relation,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedField(string $field, array $allowed): self
    {
        return new self(sprintf(
            'Field [%s] is not allowed. Allowed fields: %s.',
            $field,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedScope(string $scope, array $allowed): self
    {
        return new self(sprintf(
            'Scope [%s] is not allowed. Allowed scopes: %s.',
            $scope,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedRelation(string $relation, array $allowed): self
    {
        return new self(sprintf(
            'Relation filter [%s] is not allowed. Allowed relations: %s.',
            $relation,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedRelationCount(string $relation, array $allowed): self
    {
        return new self(sprintf(
            'Relation count [%s] is not allowed. Allowed relations: %s.',
            $relation,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedRelationColumn(string $relation, string $column, array $allowed): self
    {
        return new self(sprintf(
            'Column [%s] is not allowed for relation [%s]. Allowed columns: %s.',
            $column,
            $relation,
            self::formatAllowed($allowed),
        ));
    }

    public static function unknownScope(string $scope): self
    {
        return new self(sprintf('Scope [%s] does not exist on the repository model.', $scope));
    }

    public static function invalidScopeParameters(string $scope, int $expected, int $actual): self
    {
        return new self(sprintf(
            'Scope [%s] expects %d parameters, %d given.',
            $scope,
            $expected,
            $actual,
        ));
    }

    public static function unknownRelation(string $relation): self
    {
        return new self(sprintf('Relation [%s] does not exist on the repository model.', $relation));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function disallowedRequestFilter(string $filter, array $allowed): self
    {
        return new self(sprintf(
            'Request filter [%s] is not allowed. Allowed request filters: %s.',
            $filter,
            self::formatAllowed($allowed),
        ));
    }

    /**
     * @param  list<string>  $allowed
     */
    public static function unsupportedClause(string $clause, array $allowed): self
    {
        return new self(sprintf(
            'Request query clause [%s] is not supported. Supported clauses: %s.',
            $clause,
            self::formatAllowed($allowed),
        ));
    }

    public static function invalidClauseShape(string $clause): self
    {
        return new self(sprintf('Request query clause [%s] must be an array.', $clause));
    }

    public static function invalidPayload(): self
    {
        return new self('Request query payload must be an array.');
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function formatAllowed(array $allowed): string
    {
        if ($allowed === []) {
            return '[none]';
        }

        return implode(', ', $allowed);
    }
}
