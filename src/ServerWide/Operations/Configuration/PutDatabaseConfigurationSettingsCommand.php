<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\StringMap;
use RavenDB\Utils\RaftIdGenerator;

class PutDatabaseConfigurationSettingsCommand extends VoidRavenCommand
{
    private ?string $databaseName = null;
    private ?StringMap $configurationSettings = null;

    public function __construct(?string $databaseName, StringMap|array|null $configurationSettings)
    {
        parent::__construct();

        if (empty($databaseName)) {
            throw new IllegalArgumentException('DatabaseName cannot be null');
        }
        $this->databaseName = $databaseName;

        if ($configurationSettings == null) {
            throw new IllegalArgumentException("ConfigurationSettings cannot be null");
        }
        $this->configurationSettings = $configurationSettings;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/databases/" . $this->databaseName . "/admin/configuration/settings";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json' => $this->configurationSettings,
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ];

        return new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT, $options);
    }
}
