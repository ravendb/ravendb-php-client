<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\AbstractDocumentQuery;
use RavenDB\Documents\Session\DocumentQuery;

class AggregationDocumentQuery extends AggregationQueryBase implements AggregationDocumentQueryInterface
{
    private ?AbstractDocumentQuery $source = null;

    public function __construct(?DocumentQuery $source)
    {
        parent::__construct($source->getSession());
        $this->source = $source;
    }

    /**
     * @param Callable|FacetBase $builderOrFacets
     * @return AggregationDocumentQueryInterface
     */
    public function andAggregateBy($builderOrFacets): AggregationDocumentQueryInterface
    {
        if (is_callable($builderOrFacets)) {
            return $this->andAggregateByBuilder($builderOrFacets[0]);
        }

        return $this->andAggregateByFacet($builderOrFacets);
    }

    protected function andAggregateByBuilder(Callable $builder): AggregationDocumentQueryInterface
    {
        $f = new FacetBuilder();
        $builder($f);

        return $this->andAggregateByFacet($f->getFacet());
    }

    protected function andAggregateByFacet(FacetBase $facet): AggregationDocumentQueryInterface
    {
        $this->source->_aggregateBy($facet);
        return $this;
    }

    protected function getIndexQuery(bool $updateAfterQueryExecuted = true): IndexQuery
    {
        return $this->source->getIndexQuery();
    }

    protected function invokeAfterQueryExecuted(QueryResult $result): void
    {
        $this->source->invokeAfterQueryExecuted($result);
    }
}
