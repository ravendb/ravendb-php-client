<?php

namespace RavenDB\Documents\Queries\Suggestions;

use RavenDB\Type\StringArray;

interface SuggestionBuilderInterface
{
    function byField(?string $fieldName, null|string|StringArray|array $terms): SuggestionOperationsInterface;
}
