<?php

namespace RavenDB\Documents\Commands;

use RavenDB\Http\ResultInterface;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ExplainQueryResult implements ResultInterface
{
    /** @SerializedName ("Index") */
    private ?string $index = null;

    /** @SerializedName ("Reason") */
    private ?string $reason = null;

    public function getIndex(): ?string
    {
        return $this->index;
    }

    public function setIndex(?string $index): void
    {
        $this->index = $index;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): void
    {
        $this->reason = $reason;
    }
}
