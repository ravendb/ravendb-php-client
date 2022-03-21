<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Utils\RaftIdGenerator;

class EditClientCertificateCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private string $thumbprint;
    private DatabaseAccessArray $permissions;
    private string $name;
    private SecurityClearance $clearance;

    public function __construct(
        string $thumbprint,
        string $name,
        DatabaseAccessArray $permissions,
        SecurityClearance $clearance
    ) {
        parent::__construct();

        $this->thumbprint = $thumbprint;
        $this->name = $name;
        $this->permissions = $permissions;
        $this->clearance = $clearance;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/admin/certificates/edit';
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);

        $definition = new CertificateDefinition();
        $definition->setThumbprint($this->thumbprint);
        $definition->setPermissions($this->permissions);
        $definition->setSecurityClearance($this->clearance);
        $definition->setName($this->name);

        $request->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => $this->getMapper()->normalize($definition)
        ]);

        return $request;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
