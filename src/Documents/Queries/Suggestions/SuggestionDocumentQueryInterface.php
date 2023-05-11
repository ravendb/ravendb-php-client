<?php

namespace RavenDB\Documents\Queries\Suggestions;

use Closure;
use RavenDB\Documents\Lazy;

interface SuggestionDocumentQueryInterface
{
    function execute(): array;

    function executeLazy(?Closure $onEval = null): Lazy;

    function andSuggestUsing(SuggestionBase|Closure $suggestionOrBuilder): SuggestionDocumentQueryInterface;
}
