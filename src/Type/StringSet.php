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

    public static function fromArray(array $data): StringSet
    {
        $sa = new StringSet();

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
    // @todo: implement that StringSet can't hold two same values
}
