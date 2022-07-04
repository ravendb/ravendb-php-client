<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Type\TypedArray;

class CompareExchangeValueArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(CompareExchangeValue::class);
    }
}
