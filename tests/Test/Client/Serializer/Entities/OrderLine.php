<?php

namespace tests\RavenDB\Test\Client\Serializer\Entities;

use Symfony\Component\Serializer\Annotation\SerializedName;

class OrderLine
{
    /** @SerializedName("Id") */
    public string $id;
}
