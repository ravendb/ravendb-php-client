<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use DateTime;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\LazyRevisionsOperationsInterface;
use RavenDB\Documents\Session\Operations\GetRevisionOperation;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Type\StringArray;

class LazyRevisionOperations implements LazyRevisionsOperationsInterface
{
    protected ?DocumentSession $delegate = null;

    public function __construct(?DocumentSession $delegate)
    {
        $this->delegate = $delegate;
    }

    public function getMetadataFor(?string $id, int $start = 0, int $pageSize = 25): Lazy
    {
        $operation = GetRevisionOperation::withPagination($this->delegate, $id, $start, $pageSize);
        $lazyRevisionOperation = new LazyRevisionOperation(MetadataAsDictionary::class, $operation, LazyRevisionOperationMode::listOfMetadata());
        return $this->delegate->addLazyOperation(null, $lazyRevisionOperation, null);
    }

    public function get(?string $className, null|string|array|StringArray $changeVectors): Lazy
    {
        if (is_null($changeVectors) || is_string($changeVectors)) {
            return $this->getSingle($className, $changeVectors);
        }

        return $this->getMultiple($className, $changeVectors);
    }

    /**
     * Returns a document revision by change vector.
     *
     * @param string|null $classname
     * @param string|null $changeVector
     * @return Lazy with given change vector
     */
    function getSingle(?string $classname, ?string $changeVector): Lazy
    {
        $operation = GetRevisionOperation::forChangeVector($this->delegate, $changeVector);
        $lazyRevisionOperation = new LazyRevisionOperation($classname, $operation, LazyRevisionOperationMode::single());
        return $this->delegate->addLazyOperation($classname, $lazyRevisionOperation, null);
    }


    /**
     * Returns document revisions by change vectors.
     *
     * @param string|null $className
     * @param StringArray|array $changeVectors
     *
     * @return Lazy matching given change vectors
     */
    function getMultiple(?string $className, StringArray|array $changeVectors): Lazy
    {
        $operation = GetRevisionOperation::forChangeVectors($this->delegate, $changeVectors);
        $lazyRevisionOperation = new LazyRevisionOperation($className, $operation, LazyRevisionOperationMode::map());
        return $this->delegate->addLazyOperation(null, $lazyRevisionOperation, null);
    }

    /**
     * Returns the first revision for this document that happens before or at the specified date time.
     *
     * @param string|null $className
     * @param string|null $id
     * @param DateTime|null $date
     *
     * @return Lazy with revision
     */
    function getBeforeDate(?string $className, ?string $id, ?DateTime $date): Lazy
    {
        $operation = GetRevisionOperation::beforeDate($this->delegate, $id, $date);
        $lazyRevisionOperation = new LazyRevisionOperation($className, $operation, LazyRevisionOperationMode::single());
        return $this->delegate->addLazyOperation($className, $lazyRevisionOperation, null);
    }

    public function getFor(?string $className, ?string $id, int $start = 0, int $pageSize = 25): Lazy
    {
        $operation = GetRevisionOperation::withPagination($this->delegate, $id, $start, $pageSize);
        $lazyRevisionOperation = new LazyRevisionOperation($className, $operation, LazyRevisionOperationMode::multi());
        return $this->delegate->addLazyOperation(null, $lazyRevisionOperation, null);
    }
}
