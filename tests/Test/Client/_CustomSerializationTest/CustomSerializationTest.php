<?php

namespace tests\RavenDB\Test\Client\_CustomSerializationTest;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\DocumentStore;
use RavenDB\Extensions\JsonExtensions;
use tests\RavenDB\RemoteTestBase;

class CustomSerializationTest extends RemoteTestBase
{
    public function testSerialization(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $product1 = new Product();
                $product1->setName("iPhone");
                $product1->setPrice(Money::forDollars(9999));

                $product2 = new Product();
                $product2->setName("Camera");
                $product2->setPrice(Money::forEuro(150));

                $product3 = new Product();
                $product3->setName("Bread");
                $product3->setPrice(Money::forDollars(2));

                $session->store($product1);
                $session->store($product2);
                $session->store($product3);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            // verify if value was properly serialized
            {
                $command = GetDocumentsCommand::forSingleDocument("products/1-A", null, false);
                $store->getRequestExecutor()->execute($command);
                /** @var GetDocumentsResult $result */
                $result = $command->getResult();
                $productJson = $result->getResults()[0];

                $priceNode = $productJson["price"];
                $this->assertEquals("9999 USD", $priceNode);
            }

            //verify if query properly serialize value
            {
                $session = $store->openSession();
                try {
                    /** @var array<Product> $productsForTwoDollars */
                    $productsForTwoDollars = $session->query(Product::class)
                            ->whereEquals("price", Money::forDollars(2))
                            ->toList();

                    $this->assertCount(1, $productsForTwoDollars);

                    $this->assertEquals("Bread", $productsForTwoDollars[0]->getName());
                } finally {
                    $session->close();
                }
            }
        } finally {
            $store->close();
        }
    }

    protected function customizeStore(DocumentStore &$store): void
    {
        $conventions = $store->getConventions();

        $normalizers = [new CurrencyNormalizer()];
        $mapper = JsonExtensions::createDefaultEntityMapper($normalizers);
        $conventions->setEntityMapper($mapper);

        // @todo: remove this commented code if we don't need this method in conventions class
//        conventions.registerQueryValueConverter(Money.class, (fieldName, value, forRange, stringValue) -> {
//            stringValue.value = value.toString();
//            return true;
//        });
    }
}
