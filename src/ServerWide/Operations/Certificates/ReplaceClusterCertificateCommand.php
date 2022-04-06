<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class ReplaceClusterCertificateCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private $certBytes;
    private bool $replaceImmediately;

    public function __construct($certBytes, bool $replaceImmediately)
    {
        parent::__construct();

        if ($certBytes == null) {
            throw new IllegalArgumentException("CertBytes cannot be null");
        }

        $this->certBytes = $certBytes;
        $this->replaceImmediately = $replaceImmediately;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl()
            . "/admin/certificates/replace-cluster-cert?replaceImmediately="
            . ($this->replaceImmediately  ? "true" : "false");
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);

        $entity = [

        ];


        $request->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => [
                'Certificate' => $this->certBytes // todo: check does certBytes needs to be encoded with Base64 string
            ]
        ]);


        return $request;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
