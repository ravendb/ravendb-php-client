<?php

namespace tests\RavenDB\Infrastructure;

use RavenDB\Documents\Smuggler\DatabaseItemType;
use RavenDB\Documents\Smuggler\DatabaseItemTypeSet;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

// !status: DONE
class CreateSampleDataCommand extends VoidRavenCommand
{
    private ?DatabaseItemTypeSet $operateOnTypes = null;

    public function __construct(?DatabaseItemTypeSet $operateOnTypes = null)
    {
        parent::__construct();

        if ($operateOnTypes == null) {
            $operateOnTypes = new DatabaseItemTypeSet();
            $operateOnTypes->append(DatabaseItemType::documents());
        }

        $this->operateOnTypes = $operateOnTypes;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/studio/sample-data";

        if ($this->operateOnTypes != null) {
            $url .= '?';
            /** @var DatabaseItemType $type */
            foreach ($this->operateOnTypes as $type) {
                $url .= 'operateOnTypes=' . $type->getValue() . '&';
            }
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
