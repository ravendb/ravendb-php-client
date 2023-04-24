<?php

namespace RavenDB\Documents\Commands\MultiGet;

use RavenDB\Primitives\CleanCloseable;

class Cached implements CleanCloseable
{
    private ?int $size = null;

    public ?array $values = null;

    public function __construct(int $size)
    {
        $this->size = $size;
        $this->values = array_fill(0, $size, null);
    }

    public function close(): void
    {
        if ($this->values == null) {
            return;
        }

        for ($i = 0; $i < $this->size; $i++) {
            if ($this->values[$i] != null) {
                $this->values[$i][0]->close();
            }
        }

        $this->values = null;
    }
}
