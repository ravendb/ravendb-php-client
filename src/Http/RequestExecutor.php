<?php

namespace RavenDB\Http;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\SessionInfo;
use RavenDB\Http\Adapter\HttpClient;
use RavenDB\Type\UrlArray;

class RequestExecutor
{
    private string $databaseName;

    private DocumentConventions $conventions;

    private ?NodeSelector $nodeSelector = null;

    protected function __construct(string $databaseName, DocumentConventions $conventions)
    {
        $this->databaseName = $databaseName;
        $this->conventions = $conventions;
    }


    public static function create(UrlArray $initialUrls, string $databaseName, DocumentConventions $conventions): RequestExecutor
    {
//        RequestExecutor executor = new RequestExecutor(databaseName, certificate, keyPassword, trustStore, conventions, executorService, initialUrls);
//        executor._firstTopologyUpdate = executor.firstTopologyUpdate(initialUrls, GLOBAL_APPLICATION_IDENTIFIER);
//        return executor;

        $serverNode = new ServerNode();
        $serverNode->setDatabase($databaseName);
        $serverNode->setUrl($initialUrls[0]);

        $topology = new Topology();
        $topology->setEtag(-1);
        $topology->getServerNodes()->append($serverNode);

        $executor = new RequestExecutor($databaseName, $conventions);
        $executor->setNodeSelector(new NodeSelector($topology));

        return $executor;
    }

    public function execute(RavenCommand $command, ?SessionInfo $sessionInfo = null, ?ExecuteOptions $options = null): void
    {
        if ($options) {
            $this->executeOnSpecificNode($command, $sessionInfo, $options);
        }

        $nodeResolver = new NodeResolver($command, $sessionInfo, $this->nodeSelector);

        $executeOptions = new ExecuteOptions();
        $executeOptions->setChosenNode($nodeResolver->getNode());
        $executeOptions->setNodeIndex($nodeResolver->getNodeIndex());
        $executeOptions->setShouldRetry(true);

        $this->executeOnSpecificNode($command, $sessionInfo, $executeOptions);
    }

    private function executeOnSpecificNode(RavenCommand $command, ?SessionInfo $sessionInfo, ExecuteOptions $options)
    {
        $request = $command->createRequest($options->getChosenNode());

        if ($request == null) {
            return;
        }

        $response = $this->sendRequestToServer($options->getChosenNode(), $options->getNodeIndex(), $command, true, $sessionInfo, $request);

        if ($response == null) {
            return ;
        }
    }

    private function sendRequestToServer(ServerNode $chosenNode, int $nodeIndex, RavenCommand $command, bool $shouldRetry, ?SessionInfo $sessionInfo, HttpRequestInterface $request): HttpResponseInterface
    {
        return $this->send($chosenNode, $command, $sessionInfo, $request);
    }

    private function send(ServerNode $chosenNode, RavenCommand $command, ?SessionInfo $sessionInfo, HttpRequestInterface $request): HttpResponseInterface
    {
        $response = $command->send($this->getHttpClient(), $request);

        return $response;
    }

    private function getHttpClient(): HttpClientInterface
    {
        // todo: replace this instantiation
        return new HttpClient();
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(string $databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getNodeSelector(): ?NodeSelector
    {
        return $this->nodeSelector;
    }

    public function setNodeSelector(?NodeSelector $nodeSelector): void
    {
        $this->nodeSelector = $nodeSelector;
    }

    public function getConventions(): DocumentConventions
    {
        return $this->conventions;
    }
}
