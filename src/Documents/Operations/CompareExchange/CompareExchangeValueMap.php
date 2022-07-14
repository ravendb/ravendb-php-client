<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Type\TypedMap;

class CompareExchangeValueMap extends TypedMap
{
    public function __construct()
    {
        parent::__construct(CompareExchangeValue::class);
        $this->allowNull();
    }
}
