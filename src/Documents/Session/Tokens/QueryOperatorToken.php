<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\QueryOperator;
use RavenDB\Utils\StringBuilder;

class QueryOperatorToken extends QueryToken
{
  private QueryOperator $queryOperator;

    private function __construct(QueryOperator $queryOperator)
    {
        $this->queryOperator = $queryOperator;
    }

    public static function and(): QueryOperatorToken
    {
        return new QueryOperatorToken(QueryOperator::and());
    }

    public static function or(): QueryOperatorToken
    {
        return new QueryOperatorToken(QueryOperator::or());
    }

    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->queryOperator->isAnd()) {
            $writer->append("and");
            return;
        }

        $writer->append("or");
    }
}
