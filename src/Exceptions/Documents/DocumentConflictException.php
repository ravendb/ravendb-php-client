<?php

namespace RavenDB\Exceptions\Documents;

use RavenDB\Exceptions\BadResponseException;
use RavenDB\Exceptions\ConflictException;
use RavenDB\Extensions\JsonExtensions;

// !status: DONE
class DocumentConflictException extends ConflictException
{
    private ?string $docId;
    private int $largestEtag;

    public function __construct(string $message, ?string $docId = null, int $etag = 0)
    {
        parent::__construct($message);

        $this->docId = $docId;
        $this->largestEtag = $etag;
    }

    public static function fromMessage(string $message): DocumentConflictException
    {
        return new DocumentConflictException($message, null, 0);
    }

    public static function fromJson(string $json): DocumentConflictException
    {
        try {
            $jsonNode = JsonExtensions::getDefaultMapper()->denormalize($json, \ArrayObject::class);

            $docId = $jsonNode['DocId'];
            $message = $jsonNode['Message'];
            $largestETag = $jsonNode['LargestEtag'];

            return new DocumentConflictException($message, $docId, $largestETag);

        } catch (\Throwable $exception) {
            throw new BadResponseException('Unable to parse server response');
        }
    }

    public function getDocId(): ?string
    {
        return $this->docId;
    }

    public function setDocId(?string $docId): void
    {
        $this->docId = $docId;
    }

    public function getLargestEtag(): int
    {
        return $this->largestEtag;
    }

    public function setLargestEtag(int $largestEtag): void
    {
        $this->largestEtag = $largestEtag;
    }
}
