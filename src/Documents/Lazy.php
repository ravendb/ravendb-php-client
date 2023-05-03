<?php

namespace RavenDB\Documents;

use Closure;

/**
 * @template T
 */
class Lazy
{
    private ?Closure $valueFactory = null;
    private bool $valueCreated = false;
    /** @var T $value */
    private mixed $value;

    public function __construct(?Closure $valueFactory)
    {
        $this->valueFactory = $valueFactory;
    }

    public function isValueCreated(): bool
    {
        return $this->valueCreated;
    }

    public function getValue(): mixed
    {
        if ($this->valueCreated) {
            return $this->value;
        }
//        synchronized (this) {
//            if (!$this->valueCreated) {
                $factory = $this->valueFactory;
                $this->value = $factory();
                $this->valueCreated = true;
//            }
//        }

        return $this->value;
    }
}
