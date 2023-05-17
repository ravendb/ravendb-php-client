<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Type\StringList;
use Symfony\Component\Serializer\Annotation\SerializedName;

class SuggestionResult
{
    #[SerializedName('Name')]
    private ?string $name = null;

    #[SerializedName('Suggestions')]
    private ?StringList $suggestions = null;

    public function __construct()
    {
        $this->suggestions = new StringList();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSuggestions(): ?StringList
    {
        return $this->suggestions;
    }

    public function setSuggestions(?StringList $suggestions): void
    {
        $this->suggestions = $suggestions;
    }
}
