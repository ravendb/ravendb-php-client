<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Type\StringSet;
use RavenDB\Utils\StringUtils;

/**
 * Commands that patches multiple documents using same patch script
 * CAUTION: This command does not update session state after .saveChanges() call
 */

class BatchPatchCommandData implements CommandDataInterface
{
    private StringSet $seenIds;

    private IdAndChangeVectorList $ids;// = new ArrayList<>();

    private ?string $name = null;

    private ?PatchRequest $patch = null;

    private ?PatchRequest $patchIfMissing = null;

    /**
     * @param PatchRequest|null $patch
     * @param PatchRequest|null $patchIfMissing
     * @param array|string|IdAndChangeVector ...$ids
     */
    public function __construct(?PatchRequest $patch, ?PatchRequest $patchIfMissing, ...$ids)
    {
        $this->seenIds = new StringSet();
        $this->ids = new IdAndChangeVectorList();

        if ($patch == null) {
            throw new IllegalArgumentException("Patch cannot be null");
        }

        $this->patch = $patch;
        $this->patchIfMissing = $patchIfMissing;

        if (empty($ids)) {
            throw new IllegalArgumentException("Ids cannot be null or an emtpy collection");
        }

        foreach ($ids as $id) {
            $this->_add($id);
        }
    }

    private function _add($id): void
    {
        if (is_array($id)) {
            foreach ($id as $item) {
                $this->_add($item);
            }
            return;
        }
        if (is_string($id)) {
            $this->add($id);
            return;
        }
        if ($id instanceof IdAndChangeVector) {
            $this->add($id->getId(), $id->getChangeVector());
        }
    }

    private function add(?string $id, ?string $changeVector = null): void
    {
        if (StringUtils::isBlank($id)) {
            throw new IllegalArgumentException("Value cannot be null or whitespace");
        }

        if (!$this->seenIds->add($id)) {
            throw new IllegalStateException("Could not add ID '" . $id . "' because item with the same ID was already added");
        }

        $this->ids->append(IdAndChangeVector::create($id, $changeVector));
    }

    public function getIds(): IdAndChangeVectorList
    {
        return $this->ids;
    }

    public function getId(): ?string
    {
        throw new UnsupportedOperationException();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPatch(): ?PatchRequest
    {
        return $this->patch;
    }

    public function getPatchIfMissing(): ?PatchRequest
    {
        return $this->patchIfMissing;
    }

    public function getChangeVector(): ?string
    {
        throw new UnsupportedOperationException();
    }

    public function getType(): CommandType
    {
        return CommandType::batchPatch();
    }

    public function serialize(?DocumentConventions $conventions): array
    {
        $data = [];

        $ids = [];

        /** @var IdAndChangeVector $idAndChangeVector */
        foreach ($this->ids as $idAndChangeVector) {
            $item = [];
            $item['Id'] = $idAndChangeVector->getId();

            if ($idAndChangeVector->getChangeVector() != null) {
                $item['ChangeVector'] = $idAndChangeVector->getChangeVector();
            }

            $ids[] = $item;
        }

        $data['Ids'] = $ids;

        $data['Patch'] = $this->patch->serialize($conventions->getEntityMapper());
        $data['Type'] = 'BatchPATCH';

        if ($this->patchIfMissing != null) {
            $data['PatchIfMissing'] = $this->patchIfMissing->serialize($conventions->getEntityMapper());
        }

        return $data;
    }

    public function onBeforeSaveChanges(?InMemoryDocumentSessionOperations $session): void
    {
        // this command does not update session state after SaveChanges call!
    }
}
