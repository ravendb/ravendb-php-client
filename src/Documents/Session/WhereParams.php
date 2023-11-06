<?php

namespace RavenDB\Documents\Session;


use PHPUnit\Framework\MockObject\Rule\MethodName;

class WhereParams
{
    private string $fieldName;
    /** @var mixed|MethodName */
    private $value;
    private bool $allowWildcards;
    private bool $nestedPath;
    private bool $exact;

    public function __construct()
    {
        $this->nestedPath = false;
        $this->allowWildcards = false;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @return mixed|MethodName
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param object|MethodName $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function isAllowWildcards(): bool
    {
        return $this->allowWildcards;
    }

    public function setAllowWildcards(bool $allowWildcards): void
    {
        $this->allowWildcards = $allowWildcards;
    }

    public function isNestedPath(): bool
    {
        return $this->nestedPath;
    }

    public function setNestedPath(bool $nestedPath): void
    {
        $this->nestedPath = $nestedPath;
    }

    public function isExact(): bool
    {
        return $this->exact;
    }

    public function setExact(bool $exact): void
    {
        $this->exact = $exact;
    }


}
