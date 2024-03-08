<?php

namespace RavenDB\Documents\Session;

use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\Stack;

class FilterModeScope implements CleanCloseable
{
    private ?Stack $modeStack = null;

        public function __construct(Stack &$modeStack, bool $on)
        {
            $this->modeStack = $modeStack;
            $this->modeStack->push($on);
        }

        public function close(): void
        {
            $this->modeStack->pop();
        }
}
