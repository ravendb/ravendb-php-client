<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Type\StringArray;

class WhereMethodCall
{
    public ?MethodsType $methodType = null;
    public StringArray $parameters;
    public ?string $property = null;
}
