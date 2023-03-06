<?php

namespace RavenDB\Documents\Session;

interface SessionDocumentCountersBaseInterface
{
    /**
     * Increments by delta the value of a counter
     * @param ?string $counter the counter name
     * @param int $delta increment delta
     */
    public function increment(?string $counter, int $delta = 1): void;

    /**
     * Marks the specified document's counter for deletion. The counter will be deleted when
     * saveChanges is called.
     * @param ?string $counter The counter name
     */
    public function delete(?String $counter): void;
}
