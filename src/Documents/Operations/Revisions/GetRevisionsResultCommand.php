<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Documents\Commands\GetRevisionsCommand;
use RavenDB\Extensions\EntityMapper;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;

class GetRevisionsResultCommand extends RavenCommand
{
    private ?string $className = null;
    private ?EntityMapper $mapper = null;
    private ?GetRevisionsCommand $cmd = null;

    public function __construct(?string $className, ?string $id, ?int $start, ?int $pageSize, ?EntityMapper $mapper)
    {
        parent::__construct(RevisionsResult::class);
        $this->className = $className;
        $this->mapper = $mapper;
        $this->cmd = GetRevisionsCommand::withPagination($id, $start, $pageSize);
    }


    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $this->cmd->createUrl($serverNode);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return $this->cmd->createRequest($serverNode);
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
            if ($response == null) {
                return;
            }
            $responseNode = json_decode($response, true);
            if (!array_key_exists("Results", $responseNode)) {
                return;
            }

            $revisions = $responseNode["Results"];
            $total = intval($responseNode["TotalResults"]);

            $results = [];
            foreach ($revisions as $revision) {
                if (empty($revision)) {
                    continue;
                }

                $entity = $this->mapper->denormalize($revision, $this->className);
                $results[] = $entity;
            }

            $result = new RevisionsResult();
            $result->setResults($results);
            $result->setTotalResults($total);

            $this->result = $result;
        }
}
