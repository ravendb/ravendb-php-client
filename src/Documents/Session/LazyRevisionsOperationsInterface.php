<?php

namespace RavenDB\Documents\Session;

use DateTime;
use RavenDB\Documents\Lazy;
use RavenDB\Type\StringArray;

interface LazyRevisionsOperationsInterface
{
    /**
     * Returns all previous document revisions for specified document (with paging) ordered by most recent revision first.
     *
     * @param string|null $className
     * @param string|null $id
     * @param int $start
     * @param int $pageSize
     * @return Lazy revisions list
     */
    public function getFor(?string $className, ?string $id, int $start = 0, int $pageSize = 25): Lazy;

    /**
     * Returns all previous document revisions metadata for specified document (with paging).
     * @param string|null $id
     * @param int $start
     * @param int $pageSize
     * @return Lazy of revisions metadata
     */
    public function getMetadataFor(?string $id, int $start = 0, int $pageSize = 25): Lazy;

    public function get(?string $className, null|string|array|StringArray $changeVectors): mixed;

    /**
     * Returns a document revision by change vector.
     *
     * @param string|null $classname
     * @param string|null $changeVector
     * @return Lazy with given change vector
     */
    function getSingle(?string $classname, ?string $changeVector): Lazy;

    /**
     * Returns document revisions by change vectors.
     *
     * @param string|null $className
     * @param StringArray|array $changeVectors
     * @return Lazy with array matching given change vectors
     */
    function getMultiple(?string $className, StringArray|array $changeVectors): Lazy;

    /**
     * Returns the first revision for this document that happens before or at the specified date time.
     *
     * @param string|null $className
     * @param string|null $id
     * @param DateTime|null $date
     *
     * @return Lazy with revision
     */
    function getBeforeDate(?string $className, ?string $id, ?DateTime $date): Lazy;
}
