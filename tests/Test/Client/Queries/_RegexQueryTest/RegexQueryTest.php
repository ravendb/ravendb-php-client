<?php

namespace tests\RavenDB\Test\Client\Queries\_RegexQueryTest;

use tests\RavenDB\RemoteTestBase;

class RegexQueryTest extends RemoteTestBase
{
    public function testQueriesWithRegexFromDocumentQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $session->store(new RegexMe("I love dogs and cats"));
                $session->store(new RegexMe("I love cats"));
                $session->store(new RegexMe("I love dogs"));
                $session->store(new RegexMe("I love bats"));
                $session->store(new RegexMe("dogs love me"));
                $session->store(new RegexMe("cats love me"));
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $query = $session->advanced()
                        ->documentQuery(RegexMe::class)
                        ->whereRegex("text", "^[a-z ]{2,4}love");

                $iq = $query->getIndexQuery();

                $this->assertEquals("from 'RegexMes' where regex(text, \$p0)", $iq->getQuery());

                $this->assertEquals("^[a-z ]{2,4}love", $iq->getQueryParameters()["p0"]);

                $result = $query->toList();
                $this->assertCount(4, $result);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
