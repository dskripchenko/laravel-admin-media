<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Filters;

use Dskripchenko\LaravelAdmin\Filter\Filter;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * The collection, by its exact name: `articles` must not also list
 * `articles-archive`.
 */
final class CollectionFilter extends Filter
{
    public function type(): string
    {
        return 'input';
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if (! is_string($value) || trim($value) === '') {
            return $query;
        }

        return $query->where('collection', trim($value));
    }
}
