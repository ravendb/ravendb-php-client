<?php

namespace tests\RavenDB\Test\_FacetTestBase;

use DateTimeInterface;
use RavenDB\Type\StringList;

class Camera
{
        private ?string $id = null;

        private ?DateTimeInterface $dateOfListing = null;
        private ?string $manufacturer = null;
        private ?string $model = null;
        private ?float $cost = null;

        private ?int $zoom = null;
        private ?float $megapixels = null;
        private bool $imageStabilizer = false;
        private ?StringList $advancedFeatures = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getDateOfListing(): ?DateTimeInterface
    {
        return $this->dateOfListing;
    }

    public function setDateOfListing(?DateTimeInterface $dateOfListing): void
    {
        $this->dateOfListing = $dateOfListing;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): void
    {
        $this->model = $model;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(?float $cost): void
    {
        $this->cost = $cost;
    }

    public function getZoom(): ?int
    {
        return $this->zoom;
    }

    public function setZoom(?int $zoom): void
    {
        $this->zoom = $zoom;
    }

    public function getMegapixels(): ?float
    {
        return $this->megapixels;
    }

    public function setMegapixels(?float $megapixels): void
    {
        $this->megapixels = $megapixels;
    }

    public function isImageStabilizer(): bool
    {
        return $this->imageStabilizer;
    }

    public function setImageStabilizer(bool $imageStabilizer): void
    {
        $this->imageStabilizer = $imageStabilizer;
    }

    public function getAdvancedFeatures(): ?StringList
    {
        return $this->advancedFeatures;
    }

    /**
     * @param StringList|array|null $advancedFeatures
     */
    public function setAdvancedFeatures($advancedFeatures): void
    {
        if (is_array($advancedFeatures)) {
            $advancedFeatures = StringList::fromArray($advancedFeatures);
        }
        $this->advancedFeatures = $advancedFeatures;
    }
}
