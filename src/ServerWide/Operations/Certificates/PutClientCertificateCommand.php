<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\primitives\SharpEnum;
use RavenDB\Utils\RaftIdGenerator;

class PutClientCertificateCommand extends VoidRavenCommand implements RaftCommandInterface
{
    private string $certificate;
    private DatabaseAccessArray $permissions;
    private string $name;
    private ?SecurityClearance $clearance = null;

    public function __construct(
        ?string $name,
        ?string $certificate,
        ?DatabaseAccessArray $permissions,
        ?SecurityClearance $clearance = null
    ) {
        parent::__construct();

        if ($certificate == null) {
            throw new IllegalArgumentException('Certificate cannot be null');
        }

        if ($permissions == null) {
            throw new IllegalArgumentException('Permissions cannot be null');
        }

        $this->certificate = $certificate;
        $this->permissions = $permissions;
        $this->name = $name;
        $this->clearance = $clearance;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    protected function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . "/admin/certificates";
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request =  new HttpRequest($this->createUrl($serverNode), HttpRequest::PUT);

        $permissions = [];

        foreach ($this->permissions as $key => $value) {
            $permissions[$key] = SharpEnum::value($value);
        }

        $entity = [
            'Name' => $this->name,
            'Certificate' => $this->certificate,
            'SecurityClearance' => SharpEnum::value($this->clearance->getValue()),
            'Permissions' => $permissions
        ];


        $request->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => [
                'Entity' => $entity
            ]
        ]);

        return $request;
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
