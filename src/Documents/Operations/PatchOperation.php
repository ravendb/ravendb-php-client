<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\HttpCache;
use RavenDB\Utils\StringUtils;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;

class PatchOperation implements OperationInterface
{
    private ?string $id = null;
    private ?string $changeVector = null;
    private ?PatchRequest $patch = null;
    private ?PatchRequest $patchIfMissing = null;
    private bool $skipPatchIfChangeVectorMismatch = false;

    public function __construct(?string $id,
                                ?string $changeVector,
                                ?PatchRequest $patch,
                                ?PatchRequest $patchIfMissing = null,
                                bool $skipPatchIfChangeVectorMismatch = false
    ) {
        if ($patch == null) {
            throw new IllegalArgumentException("Patch cannot be null");
        }

        if (StringUtils::isBlank($patch->getScript())) {
            throw new IllegalArgumentException("Patch script cannot be null");
        }

        if ($patchIfMissing != null && StringUtils::isBlank($patchIfMissing->getScript())) {
            throw new IllegalArgumentException("PatchIfMissing script cannot be null");
        }

        $this->id = $id;
        $this->changeVector = $changeVector;
        $this->patch = $patch;
        $this->patchIfMissing = $patchIfMissing;
        $this->skipPatchIfChangeVectorMismatch = $skipPatchIfChangeVectorMismatch;
    }

    public function getCommand(
        ?DocumentStoreInterface $store,
        ?DocumentConventions $conventions,
        ?HttpCache $cache,
        bool $returnDebugInformation = false,
        bool $test = false
    ): RavenCommand {
        return new PatchCommand(
            $conventions,
            $this->id,
            $this->changeVector,
            $this->patch,
            $this->patchIfMissing,
            $this->skipPatchIfChangeVectorMismatch,
            $returnDebugInformation,
            $test
        );
    }
}
