<?php

namespace App\Models\Builders;

use App\Models\DocumentStatus;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Builder;

class StudentDocumentBuilder extends Builder
{
    /**
     * @param  mixed  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_string($column)) {
            [$column, $operator, $value] = $this->remapWhere($column, $operator, $value);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * @param  mixed  $column
     * @param  mixed  $values
     */
    public function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        if (is_string($column)) {
            $bare = $this->unqualifiedColumn($column);

            if ($bare === 'doc_type') {
                return parent::whereIn('document_type_ID', DocumentType::idsFor($this->stringList($values)), $boolean, $not);
            }

            if ($bare === 'status') {
                return parent::whereIn('document_status_ID', DocumentStatus::idsFor($this->stringList($values)), $boolean, $not);
            }
        }

        return parent::whereIn($column, $values, $boolean, $not);
    }

    /**
     * @return array{0: string, 1: mixed, 2: mixed}
     */
    private function remapWhere(string $column, mixed $operator, mixed $value): array
    {
        $bare = $this->unqualifiedColumn($column);

        if (! in_array($bare, ['doc_type', 'status'], true)) {
            return [$column, $operator, $value];
        }

        $idColumn = $bare === 'doc_type' ? 'document_type_ID' : 'document_status_ID';
        $slug = is_string($value) ? $value : (is_string($operator) ? $operator : null);
        $id = $bare === 'doc_type'
            ? DocumentType::idFor($slug)
            : DocumentStatus::idFor($slug);

        if ($value === null) {
            return [$idColumn, $id, null];
        }

        return [$idColumn, $operator, $id];
    }

    private function unqualifiedColumn(string $column): string
    {
        $parts = explode('.', $column);

        return (string) end($parts);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        if ($values instanceof \Traversable) {
            $values = iterator_to_array($values);
        }

        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, fn (mixed $value): bool => is_string($value)));
    }
}
