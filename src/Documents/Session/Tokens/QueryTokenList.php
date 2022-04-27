<?php

namespace RavenDB\Documents\Session\Tokens;

use InvalidArgumentException;
use RavenDB\Type\TypedArray;

class QueryTokenList extends TypedArray
{
    /**
     * @param string|QueryTokenList|null $childClassOrList
     */
    public function __construct($childClassOrList = null)
    {
        if ($childClassOrList == null) {
            parent::__construct(QueryToken::class);
            return;
        }

        if (is_string($childClassOrList)) {
            if (!is_a($childClassOrList, QueryToken::class, true)) {
                throw new InvalidArgumentException("Class must extends QueryToken class.");
            }
            parent::__construct($childClassOrList);
            return;
        }

        parent::__construct($childClassOrList->getType());

        foreach ($childClassOrList as $item) {
            $this->append($item);
        }
    }
}
