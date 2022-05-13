<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Session\DocumentSessionImplementationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringList;

/**
 * Fluent implementation for specifying include paths
 * for loading documents
 */
class MultiLoaderWithInclude implements LoaderWithIncludeInterface
{
    private ?DocumentSessionImplementationInterface $session = null;
    private ?StringList $includes = null;

    /**
     * Includes the specified path.
     * @param ?string $path Path to include
     * @return LoaderWithIncludeInterface loader with includes
     */
    public function include(?string $path): LoaderWithIncludeInterface
    {
        $this->includes->append($path);
        return $this;
    }

    /**
     * Loads the specified id or ids.
     *
     * load(string $className, StringArray ids): ObjectArray
     * load(string $className, string $id1, string $id2, string $id3...): ObjectArray
     * load(string $className, string $id): object
     *
     * @param string $className Result class
     * @param string|StringArray $ids Ids to load
     * @return ObjectArray|object|null Map id to entity
     */
    public function load(string $className, ...$ids)
    {
        if (!count($ids)) {
            throw new IllegalArgumentException('Id or ids to be loaded must be defined.');
        }

        $idsStringArray = new StringArray();
        $loadSingleObject = false;
        if ($ids[0] instanceof StringArray) {
            $idsStringArray = $ids[0];
        } else {
            $loadSingleObject = count($ids) == 1;
            foreach ($ids as $id) {
                $idsStringArray->append($id);
            }
        }

        $objects = $this->session->loadInternal($className, $idsStringArray, $this->includes);

        if ($loadSingleObject) {
            if (!count($objects)) {
                return null;
            }
            return $objects->first();
        }

        return $objects;
    }

    /**
     * Initializes a new instance of the MultiLoaderWithInclude class
     * @param DocumentSessionImplementationInterface $session Session
     */
    public function __construct(DocumentSessionImplementationInterface  $session) {
        $this->session = $session;

        $this->includes = new StringList();
    }
}
