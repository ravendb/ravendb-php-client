<?php

namespace tests\RavenDB\Infrastructure;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\VoidMaintenanceOperationInterface;
use RavenDB\Documents\Smuggler\DatabaseItemType;
use RavenDB\Documents\Smuggler\DatabaseItemTypeSet;
use RavenDB\Http\VoidRavenCommand;

class CreateSampleDataOperation implements VoidMaintenanceOperationInterface
{
    private ?DatabaseItemTypeSet $operateOnTypes = null;

    public function __construct(?DatabaseItemTypeSet $operateOnTypes = null)
    {
        if ($operateOnTypes == null) {
            $operateOnTypes = new DatabaseItemTypeSet();
            $operateOnTypes->append(DatabaseItemType::documents());
        }

        $this->operateOnTypes = $operateOnTypes;
    }


    function getCommand(DocumentConventions $conventions): VoidRavenCommand
    {
        return new CreateSampleDataCommand($this->operateOnTypes);
    }
}
