<?php

namespace tests\RavenDB\Test\Querying\_HighlightesTest;

interface SearchableInterface
{
    function getSlug(): ?string;
    function setSlug(?string $slug): void;

    function getTitle(): ?string;
    function setTitle(?string $title): void;

    function getContent(): ?string;
    function setContent(?string $content): void;
}
