<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

class MoreLikeThisBase
{
    protected ?MoreLikeThisOptions $options = null;

    public function __construct()
    {
    }

    public function getOptions(): ?MoreLikeThisOptions
    {
        return $this->options;
    }

    public function setOptions(?MoreLikeThisOptions $options): void
    {
        $this->options = $options;
    }
}
