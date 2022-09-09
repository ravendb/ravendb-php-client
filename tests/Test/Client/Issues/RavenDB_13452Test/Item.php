<?php

namespace tests\RavenDB\Test\Client\Issues\RavenDB_13452Test;

class Item
{
    private array $values = [];

    public function &getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
