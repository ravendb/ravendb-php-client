<?php

namespace tests\RavenDB\Test\Client\Serializer;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;

use RavenDB\Extensions\JsonExtensions;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use tests\RavenDB\Test\Client\Serializer\Entities\Order;
use tests\RavenDB\Test\Client\Serializer\Entities\OrderLine;

class SerializerTest extends TestCase
{
    public function testCanConvertJsonToObject(): void
    {
        $content = file_get_contents(__DIR__ . '/Data/order.json');

        $mapper = JsonExtensions::getDefaultEntityMapper();

        /** @var Order $order */
        $order = $mapper->deserialize($content, Order::class, 'json', [
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
        ]);

        $this->assertEquals(7, $order->id);
        $this->assertEquals("test", $order->name);

        $this->assertInstanceOf(OrderLine::class, $order->singleItem);
        $this->assertEquals("aaa", $order->singleItem->id);

        $this->assertInstanceOf(OrderLine::class, $order->itemsArray[0]);
        $this->assertInstanceOf(DateTimeInterface::class, $order->orderDate);
        $this->assertInstanceOf(OrderLine::class, $order->itemsAsMap['F1']);
    }
}
