<?php

namespace RavenDB\Documents\Operations\Identities;

use RavenDB\Documents\Commands\NextIdentityForCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;

class NextIdentityForOperation implements MaintenanceOperationInterface
{
    private ?string $identityName = null;

    public function __construct(?string $name)
    {
        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("The field name cannot be null or whitespace.");
        }

        $this->identityName = $name;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new NextIdentityForCommand($this->identityName);
    }
}
