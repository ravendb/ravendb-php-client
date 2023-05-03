<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Annotation\SerializedName;

class MoreLikeThisStopWords
{
    #[SerializedName("Id")]
    private ?string $id = null;

    #[SerializedName("StopWords")]
    private ?StringArray $stopWords = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getStopWords(): ?StringArray
    {
        return $this->stopWords;
    }

    public function setStopWords(null|array|StringArray $stopWords): void
    {
        $this->stopWords = is_array($stopWords) ? StringArray::fromArray($stopWords) : $stopWords;
    }
}
