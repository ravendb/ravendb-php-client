<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\StringList;

interface SessionDocumentCountersInterface extends SessionDocumentCountersBaseInterface
{
    /**
     * @return array Returns all the counters for a document.
     */
    function getAll(): array;

    /**
     * Returns the map of counter values by counter names
     * @param string|StringList|array $counters counter names
     * @return int|array|null Map of counters or single counter value
     */
    public function get(string|StringList|array $counters): null|int|array;
}
