<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;

class IndexCreation
{
//    private static final Log logger = LogFactory.getLog(IndexCreation.class);

    public static function createIndexes(
        AbstractIndexCreationTaskArray $indexes,
        ?DocumentStoreInterface $store,
        ?DocumentConventions $conventions = null
    ): void {
        if ($conventions == null) {
            $conventions = $store->getConventions();
        }

        try {
            $indexesToAdd = self::createIndexesToAdd($indexes, $conventions);
            $store->maintenance()->send(new PutIndexesOperation($indexesToAdd));
        } catch (\Throwable $e) { // For old servers that don't have the new endpoint for executing multiple indexes
            // @todo: add logger
//            logger.info("Could not create indexes in one shot (maybe using older version of RavenDB ?)", e);

            foreach ($indexes as $index) {
                $index->execute($store, $conventions);
            }
        }
    }

    public static function createIndexesToAdd(
        AbstractIndexCreationTaskArray $indexCreationTasks,
        DocumentConventions $conventions
    ): IndexDefinitionArray {

        $definitions = new IndexDefinitionArray();

        foreach ($indexCreationTasks as $creationTask) {

            $oldConventions = $creationTask->getConventions();

            try {
                $creationTask->setConventions($conventions);
                $definition = $creationTask->createIndexDefinition();
                $definition->setName($creationTask->getIndexName());
                $definition->setPriority($creationTask->getPriority() ?? IndexPriority::normal());
                $definition->setState($creationTask->getState() ?? IndexState::normal());

                $definitions->append($definition);
            } finally {
                $creationTask->setConventions($oldConventions);
            }
        }

        return $definitions;
    }
}
