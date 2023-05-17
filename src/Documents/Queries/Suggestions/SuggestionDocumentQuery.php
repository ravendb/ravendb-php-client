<?php

namespace RavenDB\Documents\Queries\Suggestions;

use Closure;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\DocumentQuery;

class SuggestionDocumentQuery extends SuggestionQueryBase implements SuggestionDocumentQueryInterface
{
    private ?DocumentQuery $source = null;

    public function __construct(?DocumentQuery $source)
    {
        parent::__construct($source->getSession());

        $this->source = $source;
    }

    protected function getIndexQuery(bool $updateAfterQueryExecuted = true): IndexQuery
    {
        return $this->source->getIndexQuery();
    }

    protected function invokeAfterQueryExecuted(?QueryResult $result): void
    {
        $this->source->invokeAfterQueryExecuted($result);
    }

    function andSuggestUsing(SuggestionBase|Closure $suggestionOrBuilder): SuggestionDocumentQueryInterface
    {
        if (is_callable($suggestionOrBuilder)) {
            return $this->andSuggestUsingBuilder($suggestionOrBuilder);
        }

        return $this->andSuggestUsingSuggestion($suggestionOrBuilder);
    }

    private function andSuggestUsingSuggestion(?SuggestionBase $suggestion): SuggestionDocumentQuery
    {
        $this->source->suggestUsing($suggestion);
        return $this;
    }


    private function andSuggestUsingBuilder(?Closure $builder): SuggestionDocumentQuery
    {
        $f = new SuggestionBuilder();
        $builder($f);

        $this->source->suggestUsing($f->getSuggestion());
        return $this;
    }
}
