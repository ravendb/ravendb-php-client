<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Utils\UrlUtils;
use RavenDB\Http\ServerNode;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\RavenCommand;
use RavenDB\Utils\StringUtils;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class PatchCommand extends RavenCommand
{
    private ?DocumentConventions $conventions = null;
    private ?string $id = null;
    private ?string $changeVector = null;
    private ?PatchPayload $patch = null;
    private bool $skipPatchIfChangeVectorMismatch = false;
    private bool $returnDebugInformation = false;
    private bool $test = false;

    public function __construct(
        ?DocumentConventions $conventions,
        ?string              $id,
        ?string              $changeVector,
        ?PatchRequest        $patch,
        ?PatchRequest        $patchIfMissing,
        bool                 $skipPatchIfChangeVectorMismatch,
        bool                 $returnDebugInformation,
        bool                 $test
    )
    {
        parent::__construct(PatchResult::class);

        $this->conventions = $conventions;

        if ($conventions == null) {
            throw new IllegalArgumentException("Conventions cannot be null");
        }

        if ($patch == null) {
            throw new IllegalArgumentException("Patch cannot be null");
        }

        if (StringUtils::isBlank($patch->getScript())) {
            throw new IllegalArgumentException("Patch.Script cannot be null");
        }

        if ($patchIfMissing != null && StringUtils::isBlank($patchIfMissing->getScript())) {
            throw new IllegalArgumentException("PatchIfMissing.Script cannot be null");
        }

        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $this->id                              = $id;
        $this->changeVector                    = $changeVector;
        $this->patch                           = new PatchPayload($patch, $patchIfMissing);
        $this->skipPatchIfChangeVectorMismatch = $skipPatchIfChangeVectorMismatch;
        $this->returnDebugInformation          = $returnDebugInformation;
        $this->test                            = $test;
    }

    public function isReadRequest(): bool
    {
        return false;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl() . "/databases/" . $serverNode->getDatabase() . "/docs?id=" . UrlUtils::escapeDataString($this->id);

        if ($this->skipPatchIfChangeVectorMismatch) {
            $url .= "&skipPatchIfChangeVectorMismatch=true";
        }

        if ($this->returnDebugInformation) {
            $url .= "&debug=true";
        }

        if ($this->test) {
            $url .= "&test=true";
        }

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $options = [
            'json'    => [
                'Patch'          =>
                    $this->patch->getPatch() != null ?
                        $this->patch->getPatch()->serialize($this->conventions->getEntityMapper()) :
                        null,
                'PatchIfMissing' =>
                    $this->patch->getPatchIfMissing() != null ?
                        $this->patch->getPatchIfMissing()->serialize($this->conventions->getEntityMapper()) :
                        null,
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        $request = new HttpRequest($this->createUrl($serverNode), HttpRequest::PATCH, $options);

        $this->addChangeVectorIfNotNull($this->changeVector, $request);
        return $request;
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            return;
        }

        $this->result = $this->getMapper()->deserialize($response, $this->getResultClass(), 'json');
    }
}
