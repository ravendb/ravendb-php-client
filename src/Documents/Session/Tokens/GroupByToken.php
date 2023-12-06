<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\GroupByMethod;
use RavenDB\Utils\StringBuilder;

class GroupByToken extends QueryToken
{
    private ?string $fieldName = null;
    private ?GroupByMethod $method;

    private function __construct(?string $fieldName, ?GroupByMethod $method) {
        $this->fieldName = $fieldName;
        $this->method = $method;
    }

    public static function create(string $fieldName, ?GroupByMethod $method = null): GroupByToken
    {
        if ($method == null) {
            $method = GroupByMethod::none();
        }

        return new GroupByToken($fieldName, $method);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        if (!$this->method->isNone()) {
            $writer->append("Array(");
        }
        $this->writeField($writer, $this->fieldName);
        if (!$this->method->isNone()) {
            $writer->append(")");
        }
    }
}
