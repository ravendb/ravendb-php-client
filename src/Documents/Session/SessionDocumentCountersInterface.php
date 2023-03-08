<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\StringList;

interface SessionDocumentCountersInterface extends SessionDocumentCountersBaseInterface
{
//        /**
//     * @return Returns all the counters for a document.
//     */
//    Map<String, Long> getAll();
//
//    /**
//     * Returns the counter by the counter name.
//     * @param counter Counter Name
//     * @return Counter value
//     */
//    Long get(String counter);

    /**
     * Returns the map of counter values by counter names
     * @param string|StringList|array $counters counter names
     * @return int|array Map of counters or single counter value
     */
    public function get(string|StringList|array $counters): int|array;
}
