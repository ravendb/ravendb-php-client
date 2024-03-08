<?php

namespace RavenDB\Type;

use Ds\Stack as DSStack;

class Stack
{
    private ?DSStack $stack = null;

    public function __construct()
    {
        $this->stack = new DSStack();
    }

    public function push(mixed $value): void
    {
        $this->stack->push($value);
    }

    public function pop(): mixed
    {
        return $this->stack->pop();
    }

    public function isEmpty(): bool
    {
        return $this->stack->isEmpty();
    }

    public function peek(): mixed
    {
        return $this->stack->peek();
    }

    public function toArray(): array
    {
        return $this->stack->toArray();
    }

    public function addAll(Stack|array $stack): void
    {
        if (is_array($stack)) {
            $this->stack->push($stack);
            return ;
        }

        foreach ($stack->toArray() as $value) {
            $this->stack->push($value);
        }
    }
}
