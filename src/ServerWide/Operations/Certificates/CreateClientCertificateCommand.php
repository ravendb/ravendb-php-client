<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RaftCommandInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RavenCommandResponseType;
use RavenDB\Http\ServerNode;
use RavenDB\primitives\SharpEnum;
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

    // @todo: Method not implemented and verified
    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::POST);

        $permissions = [];

        foreach ($this->permissions as $key => $value) {
            $permissions[$key] = SharpEnum::value($value);
        }

        $entity = [
            'Name' => $this->name,
            'SecurityClearance' => SharpEnum::value($this->clearance->getValue()),
            'Password' => $this->password,
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

//    public function setResponseRaw(?string $response, InputStream $stream): void
//    {
//        if ($response == null) {
//            $this->throwInvalidResponse();
//        }
//
//        $this->result = new CertificateRawData();
//
//        try {
////            $result->setRawData(IOUtils.toByteArray($stream));
//        } catch (\Throwable $e) {
//            $this->throwInvalidResponse($e);
//        }
//    }

    public function getRaftUniqueRequestId(): string
    {
        return RaftIdGenerator::newId();
    }
}
