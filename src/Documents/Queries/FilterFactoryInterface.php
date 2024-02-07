<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Documents\Session\WhereParams;

interface FilterFactoryInterface
{
    public function equals(string $fieldName, $value): FilterFactoryInterface;

    public function equalsWithParams(WhereParams $whereParams): FilterFactoryInterface;

    public function notEquals(?string $fieldName, $value): FilterFactoryInterface;

    public function notEqualsWithParams(WhereParams $whereParams): FilterFactoryInterface;

    public function greaterThan(?string $fieldName, $value): FilterFactoryInterface;

    public function greaterThanOrEqual(?string $fieldName, $value): FilterFactoryInterface;

    public function lessThan(?string $fieldName, $value): FilterFactoryInterface;

    public function lessThanOrEqual(?string $fieldName, $value): FilterFactoryInterface;

    public function andAlso(): FilterFactoryInterface;

    public function orElse(): FilterFactoryInterface;

    public function not(): FilterFactoryInterface;

    public function openSubclause(): FilterFactoryInterface;

    public function closeSubclause(): FilterFactoryInterface;
}
