<?php

namespace RavenDB\Documents\Queries\Suggestions;

interface SuggestionOperationsInterface
{
    function withDisplayName(?string $displayName): SuggestionOperationsInterface;

    function withOptions(?SuggestionOptions $options): SuggestionOperationsInterface;
}
