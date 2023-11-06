<?php

namespace RavenDB\Documents\Operations\Attachments;

use RavenDB\Documents\Attachments\AttachmentType;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Extensions\HttpExtensions;
use RavenDB\Http\HttpCache;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\HttpResponse;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RavenCommandResponseType;
use RavenDB\Http\ResponseDisposeHandling;
use RavenDB\Http\ServerNode;
use RavenDB\Utils\StringUtils;
use RavenDB\Utils\UrlUtils;

class GetAttachmentCommand extends RavenCommand
{
    private ?string $documentId = null;
    private ?string $name = null;
    private ?AttachmentType $type = null;
    private ?string $changeVector = null;

    public function __construct(?string $documentId, ?string $name, ?AttachmentType $type, ?string $changeVector)
    {
        parent::__construct(CloseableAttachmentResult::class);

        if (StringUtils::isBlank($documentId)) {
            throw new IllegalArgumentException("DocumentId cannot be null or empty");
        }

        if (StringUtils::isBlank($name)) {
            throw new IllegalArgumentException("Name cannot be null or empty");
        }

        if (!$type->isDocument() && $changeVector == null) {
            throw new IllegalArgumentException("Change vector cannot be null for attachment type " . $type);
        }

        $this->documentId = $documentId;
        $this->name = $name;
        $this->type = $type;
        $this->changeVector = $changeVector;

        $this->responseType = RavenCommandResponseType::empty();
    }

    public function isReadRequest(): bool
    {
        return true;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        return
            $serverNode->getUrl() .
            "/databases/" .
            $serverNode->getDatabase() .
            "/attachments?id=" .
            UrlUtils::escapeDataString($this->documentId) .
            "&name=" .
            UrlUtils::escapeDataString($this->name);
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        $request = new HttpRequest($this->createUrl($serverNode));

        if ($this->type->isRevision()) {
            $request->setMethod(HttpRequest::POST);
            $options = [
                'json' => [
                    "Type" => "Revision",
                    "ChangeVector" => $this->changeVector
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ];

            $request->setOptions($options);
        }

        return $request;
    }

    public function processResponse(?HttpCache $cache, ?HttpResponse $response, string $url): ResponseDisposeHandling
    {
        $contentType = $response->getFirstHeader('Content-Type');
        $changeVector = HttpExtensions::getEtagHeader($response);
        $hash = $response->getFirstHeader("Attachment-Hash");
        $size = 0;

        $sizeHeader = $response->getFirstHeader("Attachment-Size");
        if ($sizeHeader != null) {
            $size = intval($sizeHeader);
        }

        $attachmentDetails = new AttachmentDetails();
        $attachmentDetails->setContentType($contentType);
        $attachmentDetails->setName($this->name);
        $attachmentDetails->setHash($hash);
        $attachmentDetails->setSize($size);
        $attachmentDetails->setChangeVector($changeVector);
        $attachmentDetails->setDocumentId($this->documentId);

        $this->result = new CloseableAttachmentResult($response, $attachmentDetails);

        return ResponseDisposeHandling::manually();
    }
}
