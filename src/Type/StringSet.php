<?php

namespace RavenDB\Type;

class StringSet extends StringArray
{
    public function __construct(?StringSet $set = null)
    {
        parent::__construct();

        if ($set !== null) {
            foreach ($set->getArrayCopy() as $value) {
                $this->append($value);
            }
        }
    }

    public function add(string $value): bool
    {
        if (in_array($value, $this->getArrayCopy())) {
            return false;
        }

        $this->append($value);
        return true;
    }
}
