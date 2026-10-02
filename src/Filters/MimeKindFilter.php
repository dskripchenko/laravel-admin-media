<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminMedia\Filters;

use Dskripchenko\LaravelAdmin\Filter\Filter;
use Dskripchenko\LaravelAdmin\I18n\Localize;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * The kind of file, by the start of its MIME type: 'image/', 'video/',
 * 'application/pdf'. There is no `mime_kind` column; the filter matches the
 * `mime` column's prefix.
 */
final class MimeKindFilter extends Filter
{
    /** @var array<string, string> */
    private array $options = [];

    public function type(): string
    {
        return 'options';
    }

    /**
     * @param  array<string, string>  $options  MIME prefix => label
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        if (! is_string($value) || $value === '') {
            return $query;
        }

        return $query->where('mime', 'like', addcslashes($value, '%_\\').'%');
    }

    public function toArray(): array
    {
        $base = parent::toArray();
        $base['options'] = array_map(
            static fn (string $label, string $value): array => ['value' => $value, 'label' => Localize::string($label)],
            $this->options,
            array_keys($this->options),
        );

        return $base;
    }
}
