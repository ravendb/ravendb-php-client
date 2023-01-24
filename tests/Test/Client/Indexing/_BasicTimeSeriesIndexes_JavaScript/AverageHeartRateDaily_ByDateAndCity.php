<?php

namespace tests\RavenDB\Test\Client\Indexing\_BasicTimeSeriesIndexes_JavaScript;

class AverageHeartRateDaily_ByDateAndCity // extends AbstractJavaScriptTimeSeriesIndexCreationTask
{
//    public AverageHeartRateDaily_ByDateAndCity() {
//            setMaps(Collections.singleton("timeSeries.map('Users', 'heartRate', function (ts) {\n" +
//                    "return ts.Entries.map(entry => ({\n" +
//                    "        heartBeat: entry.Value,\n" +
//                    "        date: new Date(entry.Timestamp.getFullYear(), entry.Timestamp.getMonth(), entry.Timestamp.getDate()),\n" +
//                    "        city: load(entry.Tag, 'Addresses').city,\n" +
//                    "        count: 1\n" +
//                    "    }));\n" +
//                    "})"));
//
//            setReduce("groupBy(r => ({ date: r.date, city: r.city }))\n" +
//                    " .aggregate(g => ({\n" +
//                    "     heartBeat: g.values.reduce((total, val) => val.heartBeat + total, 0) / g.values.reduce((total, val) => val.count + total, 0),\n" +
//                    "     date: g.key.date,\n" +
//                    "     city: g.key.city\n" +
//                    "     count: g.values.reduce((total, val) => val.count + total, 0)\n" +
//                    " }))");
//        }
}
