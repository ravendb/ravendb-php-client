<?php

namespace RavenDB\Documents\Queries\Highlighting;

use RavenDB\Type\StringArray;

class HighlightingOptions
{
    private ?string $groupKey;
    private ?StringArray $preTags = null;
    private ?StringArray $postTags = null;

    public function getGroupKey(): ?string
    {
        return $this->groupKey;
    }

    public function setGroupKey(?string $groupKey): void
    {
        $this->groupKey = $groupKey;
    }

    public function getPreTags(): ?StringArray
    {
        return $this->preTags;
    }

    /**
     * @param null|StringArray|array $preTags
     */
    public function setPreTags($preTags): void
    {
        $this->preTags = is_array($preTags) ? StringArray::fromArray($preTags) : $preTags;
    }

    public function getPostTags(): ?StringArray
    {
        return $this->postTags;
    }

    /**
     * @param null|StringArray|array $postTags
     */
    public function setPostTags($postTags): void
    {
        $this->postTags = is_array($postTags) ? StringArray::fromArray($postTags) : $postTags;
    }
}
