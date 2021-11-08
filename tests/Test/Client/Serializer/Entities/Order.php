<?php

namespace tests\RavenDB\Test\Client\Serializer\Entities;

use DateTimeInterface;

use Symfony\Component\Serializer\Annotation\SerializedName;

class Order
{
    /** @SerializedName("OrderDate") */
    public ?DateTimeInterface $orderDate = null;

    /** @SerializedName("Id") */
    public int $id = 0;

    /** @SerializedName("Name") */
    public string $name = '';

    /** @SerializedName("SingleItem") */
    public ?OrderLine $singleItem = null;

    /** @SerializedName("ItemsArray") */
    public ?OrderLineArray $itemsArray = null;

    /**
     * @SerializedName("ItemsAsMap")
     */
    public ?OrderLineMap $itemsAsMap = null;

}
