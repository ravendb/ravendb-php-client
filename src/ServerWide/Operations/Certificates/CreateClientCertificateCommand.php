<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponseInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RavenCommandResponseType;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\SharpEnum;
use RavenDB\Utils\RaftIdGenerator;

class CreateClientCertificateCommand extends RavenCommand implements RaftCommandInterface
{
    private string $name;
    private DatabaseAccessArray $permissions;
    private ?SecurityClearance $clearance;
    private ?string $password;

    public function __construct(?string $name, ?DatabaseAccessArray $permissions, ?SecurityClearance $clearance, ?string $password)
    {
        parent::__construct(CertificateRawData::class);

        if ($name == null) {
            throw new IllegalArgumentException("Name cannot be null");
        }

        if ($permissions == null) {
            throw new IllegalArgumentException("Permission cannot be null");
        }

        $this->name = $name;
        $this->permissions = $permissions;
        $this->clearance = $clearance;
        $this->password = $password;

        $this->responseType = RavenCommandResponseType::raw();
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return $serverNode->getUrl() . '/admin/certificates';
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);

        $permissions = [];
        foreach ($this->permissions as $key => $value) {
            $permissions[$key] = SharpEnum::value($value);
        }

        $request->setOptions([
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json' => [
                'Name' => $this->name,
                'SecurityClearance' => SharpEnum::value($this->clearance->getValue()),
                'Password' => $this->password,
                'Permissions' => count($permissions) ? $permissions : null
            ]
        ]);

        return $request;
    }

    /**
     * @throws \RavenDB\Exceptions\IllegalStateException
     */
    public function setResponseRaw(HttpResponseInterface $response): void
    {
        $content = $response->getContent();
        if (empty($content)) {
            $this->throwInvalidResponse();
        }

        $this->result = new CertificateRawData();

        try {
            $this->result->setRawData($content);
        } catch (\Throwable $e) {
            $this->throwInvalidResponse($e);
        }
    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
