<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Annotation\SerializedName;

class RevisionsCollectionConfiguration
{
    #[SerializedName('MinimumRevisionsToKeep')]
    private ?int $minimumRevisionsToKeep = null;

    #[SerializedName('MinimumRevisionAgeToKeep')]
    private ?Duration $minimumRevisionAgeToKeep = null;

    #[SerializedName('Disabled')]
    private bool $disabled = false;

    #[SerializedName('PurgeOnDelete')]
    private bool $purgeOnDelete = false;

    #[SerializedName('MaximumRevisionsToDeleteUponDocumentUpdate')]
    private ?int $maximumRevisionsToDeleteUponDocumentUpdate = null;

    public function getMinimumRevisionsToKeep(): ?int
    {
        return $this->minimumRevisionsToKeep;
    }

    public function setMinimumRevisionsToKeep(?int $minimumRevisionsToKeep): void
    {
        $this->minimumRevisionsToKeep = $minimumRevisionsToKeep;
    }

    public function getMinimumRevisionAgeToKeep(): ?Duration
    {
        return $this->minimumRevisionAgeToKeep;
    }

    public function setMinimumRevisionAgeToKeep(?Duration $minimumRevisionAgeToKeep): void
    {
        $this->minimumRevisionAgeToKeep = $minimumRevisionAgeToKeep;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function isPurgeOnDelete(): bool
    {
        return $this->purgeOnDelete;
    }

    public function setPurgeOnDelete(bool $purgeOnDelete): void
    {
        $this->purgeOnDelete = $purgeOnDelete;
    }

    public function getMaximumRevisionsToDeleteUponDocumentUpdate(): ?int
    {
        return $this->maximumRevisionsToDeleteUponDocumentUpdate;
    }

    public function setMaximumRevisionsToDeleteUponDocumentUpdate(?int $maximumRevisionsToDeleteUponDocumentUpdate): void
    {
        $this->maximumRevisionsToDeleteUponDocumentUpdate = $maximumRevisionsToDeleteUponDocumentUpdate;
    }
}
