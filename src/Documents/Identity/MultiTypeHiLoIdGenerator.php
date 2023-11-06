<?php

namespace RavenDB\Documents\Identity;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStore;
use RavenDB\Type\Collection;
use RavenDB\Utils\StringUtils;

/**
 *  Generate a hilo ID for each given type
 */

class MultiTypeHiLoIdGenerator
{
    private ?Collection $idGeneratorsByTag = null;
    protected ?DocumentStore $store;
    protected ?string $dbName;
    protected ?DocumentConventions $conventions;
    private string $identityPartsSeparator;

    public function __construct(?DocumentStore $store, ?string $dbName)
    {
        $this->idGeneratorsByTag = new Collection();

        $this->store = $store;
        $this->dbName = $dbName;
        $this->conventions = $store->getRequestExecutor($dbName)->getConventions();
        $this->identityPartsSeparator = $this->conventions->getIdentityPartsSeparator();
    }

    public function generateDocumentId(object $entity): ?string
    {
        $identityPartsSeparator = $this->conventions->getIdentityPartsSeparator();
        if ($this->identityPartsSeparator != $identityPartsSeparator) {
            $this->maybeRefresh($identityPartsSeparator);
        }

        $typeTagName = $this->conventions->getCollectionName($entity);

        if (StringUtils::isEmpty($typeTagName)) {
            return null;
        }

        $f = $this->conventions->getTransformClassCollectionNameToDocumentIdPrefix();
        $tag = $f($typeTagName);

        if (!$this->idGeneratorsByTag->offsetExists($tag)) {
            $value = $this->createGeneratorFor($tag);
            $this->idGeneratorsByTag->offsetSet($tag, $value);
        }

        /** @var HiLoIdGenerator $value */
        $value = $this->idGeneratorsByTag->offsetGet($tag);

        return $value->generateDocumentId($entity);
    }

    private function maybeRefresh(?string $identityPartsSeparator): void
    {
        if ($this->identityPartsSeparator == $identityPartsSeparator) {
            return;
        }

        $idGenerators = new Collection($this->idGeneratorsByTag->getArrayCopy());

        $this->idGeneratorsByTag->clear();
        $this->identityPartsSeparator = $identityPartsSeparator;

        if (!empty($idGenerators)) {
            try {
                self::returnUnusedRangeInternal($idGenerators);
            } catch (\Throwable $e) {
                // ignored
            }
        }
    }

    public function generateNextIdFor(?string $collectionName): int
    {
        $value = null;
        if ($this->idGeneratorsByTag->containsValue($collectionName)) {
            $value = $this->idGeneratorsByTag[$collectionName];
        }

        if ($value != null) {
            return $value->nextId();
        }

        $value = $this->createGeneratorFor($collectionName);
        $this->idGeneratorsByTag->offsetSet($collectionName, $value);

        return $value->nextId();
    }

    protected function createGeneratorFor(string $tag): HiLoIdGenerator
    {
        return new HiLoIdGenerator($tag, $this->store, $this->dbName, $this->identityPartsSeparator);
    }

    public function returnUnusedRange(): void
    {
        self::returnUnusedRangeInternal($this->idGeneratorsByTag);
    }

    private static function returnUnusedRangeInternal(Collection $generators): void
    {
        foreach ($generators as $generator) {
            $generator->returnUnusedRange();
        }
    }
}
