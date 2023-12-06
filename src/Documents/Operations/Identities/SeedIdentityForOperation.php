<?php

namespace RavenDB\Documents\Operations\Identities;

use RavenDB\Documents\Commands\SeedIdentityForCommand;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\MaintenanceOperationInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;

class SeedIdentityForOperation implements MaintenanceOperationInterface
{
    private string $identityName;
    private int $identityValue;
    private bool $forceUpdate = false;

    public function __construct(string $name, int $value, bool $forceUpdate = false)
    {
        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("The field name cannot be null or whitespace.");
        }

        $this->identityName = $name;
        $this->identityValue = $value;
        $this->forceUpdate = $forceUpdate;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new SeedIdentityForCommand($this->identityName, $this->identityValue, $this->forceUpdate);
    }
}
