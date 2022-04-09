<?php

namespace RavenDB\Documents\Session\Tokens;

use InvalidArgumentException;
use RavenDB\Type\TypedArray;

class QueryTokenList extends TypedArray
{
    public function __construct(string $childClass = null)
    {
        if ($childClass !== null) {
            if (!is_a($childClass, QueryToken::class, true)) {
                throw new InvalidArgumentException("Class must extends QueryToken class.");
            }
        }

        parent::__construct($childClass ?? QueryToken::class);
    }
}
