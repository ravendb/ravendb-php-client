<?php

namespace tests\RavenDB\Test\Client\Query;

use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Session\BeforeQueryEventArgs;
use RavenDB\Documents\Session\DocumentQueryInterface;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Query\Entity\Article;

class QueryTest extends RemoteTestBase
{
    public function testCreateClausesForQueryDynamicallyWithOnBeforeQueryEvent()
    {
        $store = $this->getDocumentStore();

        try {
            $id1 = 'users/1';
            $id2 = 'users/2';

            $session = $store->openSession();

            try {
                $article1 = new Article();
                $article1->setTitle("foo");
                $article1->setDescription("bar");
                $article1->setDeleted(false);
                $session->store($article1, $id1);

                $article2 = new Article();
                $article2->setTitle("foo");
                $article2->setDescription("bar");
                $article2->setDeleted(true);
                $session->store($article2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->addBeforeQueryListener(function($sender, BeforeQueryEventArgs $event) {
                    /** @var DocumentQueryInterface $queryToBeExecuted */
                    $queryToBeExecuted = $event->getQueryCustomization()->getQuery();
                    $queryToBeExecuted->andAlso(true);
                    $queryToBeExecuted->whereEquals("deleted", true);
                });

                $query = $session->query(Article::class)
                    ->search('title', 'foo')
                    ->search('description', 'bar',  SearchOperator::or())
                ;

                $result = $query->toList();

                $this->assertEquals(
                    "from 'Articles' where (search(title, \$p0) or search(description, \$p1)) and deleted = \$p2",
                    $query->toString()
                );

//                $this->assertCount(1, $result);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
