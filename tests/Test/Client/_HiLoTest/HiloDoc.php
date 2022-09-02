<?php

namespace tests\RavenDB\Test\Client\_HiLoTest;

use Symfony\Component\Serializer\Annotation\SerializedName;

class HiloDoc
{
    /** @SerializedName ("Max")  */
    private ?int $max = null;

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function setMax(?int $max): void
    {
        $this->max = $max;
    }
}
