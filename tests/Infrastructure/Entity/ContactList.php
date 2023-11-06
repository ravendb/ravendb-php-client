<?php

namespace tests\RavenDB\Infrastructure\Entity;

use RavenDB\Type\TypedList;

class ContactList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Contact::class);
    }
}
