<?php

namespace tests\RavenDB\Infrastructure\Entity;

use RavenDB\Type\TypedList;

// !status: DONE
class OrderLineList extends TypedList
{
    public function __construct()
    {
        parent::__construct(OrderLine::class);
    }

    public static function fromArray(array $array): OrderLineList
    {
        $orderLineList = new OrderLineList();
        foreach ($array as $item) {
            $orderLineList->append($item);
        }
        return $orderLineList;
    }
}
