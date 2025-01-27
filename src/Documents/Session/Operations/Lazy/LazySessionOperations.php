<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use Closure;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Session\ConditionalLoadResult;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\Loaders\LazyLoaderWithIncludeInterface;
use RavenDB\Documents\Session\Loaders\LazyMultiLoaderWithInclude;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class LazySessionOperations implements LazySessionOperationsInterface
{
    protected ?DocumentSession $delegate = null;

    public function __construct(?DocumentSession $delegate)
    {
        $this->delegate = $delegate;
    }

    public function include(?string $path): LazyLoaderWithIncludeInterface
    {
        return (new LazyMultiLoaderWithInclude($this->delegate))->include($path);
    }

    /**
     * Loads the specified entity/entities with the specified id.
     *
     * @param string|null $className Result class
     * @param string|array $ids Identifier of an entity/entities that will be loaded.
     * @param Closure|null $onEval Action to be executed on evaluation.
     * @return Lazy
     */
    public function load(?string $className, string|array $ids, ?Closure $onEval = null): Lazy
    {
        if (is_string($ids)) {
            return $this->_loadId($className, $ids, $onEval);
        }

        return $this->_loadIds($className, $ids, $onEval);
    }

    private function _loadId(?string $className, string$id, ?Closure $onEval = null): Lazy
    {
        if ($this->delegate->isLoaded($id)) {
            $delegate = $this->delegate;
            return new Lazy(function() use ($delegate, $className, $id) { return $delegate->load($className, $id); });
        }

        $lazyLoadOperation = (new LazyLoadOperation($className, $this->delegate, (new LoadOperation($this->delegate))->byId($id)))->byId($id);
        return $this->delegate->addLazyOperation($className, $lazyLoadOperation, $onEval);

    }

    public function _loadIds(?string $className, array $ids, ?Closure $onEval = null): Lazy
    {
        return $this->delegate->lazyLoadInternal($className, $ids, [], $onEval);
    }

    public function loadStartingWith(?string $className, ?string $idPrefix, ?string $matches = null, int $start = 0, int $pageSize = 25, ?string $exclude = null, ?string $startAfter = null): Lazy
    {
        $operation = new LazyStartsWithOperation($className, $idPrefix, $matches, $exclude, $start, $pageSize, $this->delegate, $startAfter);

        return $this->delegate->addLazyOperation(null, $operation, null);
    }

    public function conditionalLoad(?string $className, ?string $id, ?string $changeVector): Lazy // Lazy <ConditionalLoadResult <TResult>>
    {
        if (StringUtils::isEmpty($id)) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        if ($this->delegate->isLoaded($id)) {
            return new Lazy(function() use ($className, $id) {
                $entity = $this->delegate->load($className, $id);
                if ($entity == null) {
                    return ConditionalLoadResult::create(null, null);
                }
                $cv = $this->delegate->advanced()->getChangeVectorFor($entity);
                return ConditionalLoadResult::create($entity,$cv);
            });
        }

        if (StringUtils::isEmpty($changeVector)) {
            throw new IllegalArgumentException("The requested document with id '"
              . $id . "' is not loaded into the session and could not conditional load when changeVector is null or empty.");
        }

        $lazyLoadOperation = new LazyConditionalLoadOperation($className, $id, $changeVector, $this->delegate);

        //?string $className, LazyOperationInterface $operation, ?Closure $onEval = null): Lazy
        return $this->delegate->addLazyOperation(ConditionalLoadResult::class, $lazyLoadOperation);

    }

    //TBD expr ILazyLoaderWithInclude<T> ILazySessionOperations.Include<T>(Expression<Func<T, string>> path)
    //TBD expr ILazyLoaderWithInclude<T> ILazySessionOperations.Include<T>(Expression<Func<T, IEnumerable<string>>> path)
}
