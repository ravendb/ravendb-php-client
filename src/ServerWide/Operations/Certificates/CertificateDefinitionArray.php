<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\TypedArray;

class CertificateDefinitionArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(CertificateDefinition::class);
    }
}
