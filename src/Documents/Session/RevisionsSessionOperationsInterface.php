<?php

namespace RavenDB\Documents\Session;

use DateTime;
use RavenDB\Type\StringArray;

interface RevisionsSessionOperationsInterface
{
    /**
     * Returns all previous document revisions for specified document (with paging) ordered by most recent revisions first.
     *
     * @param string|null $className entity class
     * @param string|null $id Identifier of a entity that will be loaded.
     * @param int $start Range start
     * @param int $pageSize maximum number of documents that will be retrieved
     * @return array List of revisions
     */
    public function getFor(?string $className, ?string $id, int $start = 0, int $pageSize = 25): array;

    /**
     * Returns all previous document revisions for specified document (with paging) ordered by most recent revisions first.
     *
     * @param string|null $id
     * @param int $start
     * @param int $pageSize
     * @return array of revisions metadata
     */
    public function getMetadataFor(?string $id, int $start = 0, int $pageSize = 25): array;

    /**
     * Returns a document revision(s) by change vector(s).
     *
     * @param string|null $className
     * @param string|array|StringArray|null $changeVectors Change vector or change vectors
     *
     * @return mixed
     */
    public function get(?string $className, null|string|array|StringArray $changeVectors): mixed;

    /**
     * Returns a document revision by change vector.
     *
     * @param string|null $classname
     * @param string|null $changeVector
     *
     * @return object|null with given change vector
     */
    function getSingle(?string $classname, ?string $changeVector): ?object;

    /**
     * Returns document revisions by change vectors.
     *
     * @param string|null $className
     * @param StringArray|array $changeVectors
     *
     * @return array matching given change vectors
     */
    function getMultiple(?string $className, StringArray|array $changeVectors): array;

    /**
     * Returns the first revision for this document that happens before or at the specified date
     * @param string|null $className
     * @param string|null $id
     * @param DateTime $date
     * @return object|null changed before specified date
     */
    function getBeforeDate(?string $className, ?string $id, DateTime $date): ?object;

    /**
     * Make the session create a revision for the specified document id or entity.
     * Revision will be created Even If:
     *
     * 1. Revisions configuration is Not set for the collection
     * 2. Document was Not modified
     * @param string|object|null $idOrEntity
     * @param ForceRevisionStrategy|null $strategy
     */
    public function forceRevisionCreationFor(null|string|object $idOrEntity, ?ForceRevisionStrategy $strategy = null): void;

    /**
     * Returns the number of revisions for specified document.
     * @param ?string $id Document id to use
     * @return int count
     */
    function getCountFor(?string $id): int;

    /**
     * Access the lazy revisions operations
     * @return LazyRevisionsOperationsInterface revisions operations
     */
    function lazily(): LazyRevisionsOperationsInterface;
}
