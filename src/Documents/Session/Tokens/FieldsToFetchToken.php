<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringBuilder;

// !status: DONE
class FieldsToFetchToken extends QueryToken
{
    public StringArray $fieldsToFetch;
    public ?StringArray $projections;
    public bool $customFunction;
    public string $sourceAlias;

    private function __construct(StringArray $fieldsToFetch, ?StringArray $projections, bool $customFunction, String $sourceAlias)
    {
        $this->fieldsToFetch = $fieldsToFetch;
        $this->projections = $projections;
        $this->customFunction = $customFunction;
        $this->sourceAlias = $sourceAlias;
    }

    public static function create(?StringArray $fieldsToFetch, ?StringArray $projections, bool $customFunction, string $sourceAlias): FieldsToFetchToken
    {
        if ($fieldsToFetch == null || $fieldsToFetch->isEmpty()) {
            throw new IllegalArgumentException("fieldToFetch cannot be null");
        }

        if (!$customFunction && ($projections != null) && ($projections->count() != $fieldsToFetch->count())) {
            throw new IllegalArgumentException("Length of projections must be the same as length of field to fetch");
        }

        return new FieldsToFetchToken($fieldsToFetch, $projections, $customFunction, $sourceAlias);
    }

    public function writeTo(StringBuilder $writer): void
    {
        for ($i = 0; $i < $this->fieldsToFetch->count(); $i++) {
            $fieldToFetch = $this->fieldsToFetch->offsetGet($i);

            if ($i > 0) {
                $writer->append(", ");
            }

            if ($fieldToFetch == null) {
                $writer->append("null");
            } else {
                $this->writeField($writer, $fieldToFetch);
            }

            if ($this->customFunction) {
                continue;
            }

            $projection = $this->projections != null ? $$this->projections[$i] : null;

            if ($projection == null || $projection->equals($fieldToFetch)) {
                continue;
            }

            $writer->append(" as ");
            $writer->append($projection);
        }
    }
}
