<?php

namespace RavenDB\Utils;

class Size
{
    private int $sizeInBytes = 0;
    private ?string $humaneSize = null;

    public function getSizeInBytes(): int
    {
        return $this->sizeInBytes;
    }

    public function setSizeInBytes(int $sizeInBytes): void
    {
        $this->sizeInBytes = $sizeInBytes;
    }

    public function getHumaneSize(): ?string
    {
        return $this->humaneSize;
    }

    public function setHumaneSize(?string $humaneSize): void
    {
        $this->humaneSize = $humaneSize;
    }
}
