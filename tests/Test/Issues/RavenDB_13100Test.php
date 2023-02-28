<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Indexes\IndexDefinitionHelper;
use RavenDB\Documents\Indexes\IndexSourceType;
use tests\RavenDB\RemoteTestBase;

class RavenDB_13100Test extends RemoteTestBase
{
    public function testCanDetectTimeSeriesIndexSourceMethodSyntax(): void
    {
        $map = "timeSeries.Companies.SelectMany(ts => ts.Entries, (ts, entry) => new {" .
            "   HeartBeat = entry.Values[0], " .
            "   Date = entry.Timestamp.Date, " .
            "   User = ts.DocumentId " .
            "});";

        $this->assertEquals(IndexSourceType::timeSeries(), IndexDefinitionHelper::detectStaticIndexSourceType($map));
    }

    public function testCanDetectDocumentsIndexSourceMethodSyntax(): void
    {
        $map = "docs.Users.OrderBy(user => user.Id).Select(user => new { user.Name })";

        $this->assertEquals(IndexSourceType::documents(), IndexDefinitionHelper::detectStaticIndexSourceType($map));
    }

    public function testCanDetectTimeSeriesIndexSourceLinqSyntaxAllTs() {
        $map = "from ts in timeSeries";
        $this->assertEquals(IndexSourceType::timeSeries(), IndexDefinitionHelper::detectStaticIndexSourceType($map));
    }

    public function testCanDetectTimeSeriesIndexSourceLinqSyntaxSingleTs(): void {
        $map = "from ts in timeSeries.Users";
        $this->assertEquals(IndexSourceType::timeSeries(), IndexDefinitionHelper::detectStaticIndexSourceType($map));
    }

    public function testCanDetectTimeSeriesIndexSourceLinqSyntaxCanStripWhiteSpace(): void {
        $map = "\t\t  \t from    ts  \t \t in  \t \t timeSeries.Users";
        $this->assertEquals(IndexSourceType::timeSeries(), IndexDefinitionHelper::detectStaticIndexSourceType($map));
    }
}
