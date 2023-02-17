<?php

namespace tests\RavenDB\Test\Issues\RDBC_501Test;

use RavenDB\Documents\Queries\TimeSeries\TimeSeriesAggregationResult;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RavenTestHelper;
use tests\RavenDB\RemoteTestBase;

class RDBC_501Test extends RemoteTestBase
{
    public function testShouldProperlyMapTypedEntries(): void
    {
        $store = $this->getDocumentStore();
        try {
            $baseline = RavenTestHelper::utcToday();

            $session = $store->openSession();
            try {
                $symbol = new MarkerSymbol();
                $session->store($symbol, "markerSymbols/1-A");

                $price1 = new SymbolPrice();
                $price1->low = 1;
                $price1->high = 10;
                $price1->open = 4;
                $price1->close = 7;

                $price2 = new SymbolPrice();
                $price2->low = 21;
                $price2->high = 210;
                $price2->open = 24;
                $price2->close = 27;

                $price3 = new SymbolPrice();
                $price3->low = 321;
                $price3->high = 310;
                $price3->open = 34;
                $price3->close = 37;

                $tsf = $session->typedTimeSeriesFor(SymbolPrice::class, $symbol, "history");

                $tsf->append(DateUtils::addHours($baseline, 1), $price1);
                $tsf->append(DateUtils::addHours($baseline, 2), $price2);
                $tsf->append(DateUtils::addDays($baseline, 2), $price3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var TimeSeriesAggregationResult $aggregatedHistoryQueryResult */
                $aggregatedHistoryQueryResult = $session->query(MarkerSymbol::class)
                        ->selectTimeSeries(TimeSeriesAggregationResult::class, function($b) { $b->raw("from history\n" .
                                "          group by '1 days'\n" .
                                "          select first(), last(), min(), max()"); })
                        ->first();

                $this->assertCount(2, $aggregatedHistoryQueryResult->getResults());

                $typed = $aggregatedHistoryQueryResult->asTypedResult(SymbolPrice::class);

                $this->assertCount(2, $typed->getResults());

                $firstResult = $typed->getResults()[0];
                $this->assertEquals(4, $firstResult->getMin()->getOpen());
                $this->assertEquals(7, $firstResult->getMin()->getClose());
                $this->assertEquals(1, $firstResult->getMin()->getLow());
                $this->assertEquals(10, $firstResult->getMin()->getHigh());

                $this->assertEquals(4, $firstResult->getFirst()->getOpen());
                $this->assertEquals(7, $firstResult->getFirst()->getClose());
                $this->assertEquals(1, $firstResult->getFirst()->getLow());
                $this->assertEquals(10, $firstResult->getFirst()->getHigh());

                $secondResult = $typed->getResults()[1];

                $this->assertEquals(34, $secondResult->getMin()->getOpen());
                $this->assertEquals(37, $secondResult->getMin()->getClose());
                $this->assertEquals(321, $secondResult->getMin()->getLow());
                $this->assertEquals(310, $secondResult->getMin()->getHigh());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
