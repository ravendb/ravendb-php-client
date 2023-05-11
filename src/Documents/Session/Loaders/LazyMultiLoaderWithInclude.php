<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Lazy;
use RavenDB\Documents\Session\DocumentSessionImplementationInterface;
use RavenDB\Type\StringArray;

class LazyMultiLoaderWithInclude implements LazyLoaderWithIncludeInterface
{
    private ?DocumentSessionImplementationInterface $session = null;
    private array $includes = [];

    public function __construct(DocumentSessionImplementationInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Includes the specified path.
     */
    public function include(?string $path): LazyLoaderWithIncludeInterface
    {
        $this->includes[]  = $path;
        return $this;
    }

    public function load(?string $className, string|array ...$ids): Lazy
    {
        $list = [];

        foreach ($ids as $id) {
            if (is_string($id)) {
                $list[] = $id;
            }
            if (is_array($id)) {
                $id = StringArray::fromArray($id);
                $list = array_merge($list, $id);
            }
        }

        return $this->session->lazyLoadInternal($className, $list, $this->includes, null);
    }

    public function loadSingle(?string $className, string $id): Lazy
    {
        $results = $this->session->lazyLoadInternal($className, [ $id ], $this->includes, null);
        return new Lazy(function() use ($results) {
            return $results->getValue()[array_key_first($results->getValue())];
//            return $results->getValue()->first(); // check what is returned from getValue() / array or ArrayObject
        });
    }
}
