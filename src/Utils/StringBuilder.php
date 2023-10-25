<?php

namespace RavenDB\Utils;

class StringBuilder
{
    private string $s;

    public function __construct(string $value = '')
    {
        $this->s = $value;
    }

    public function append(?string $value): StringBuilder
    {
        if ($value != null) {
            $this->s .= $value;
        }
        return $this;
    }

    public function insert(int $offset, ?string $value): StringBuilder
    {
        if ($value != null) {
            $oldString = $this->s;
            $this->s = substr($oldString, 0, $offset) . $value . substr($oldString, $offset);
        }

        return $this;
    }

    public function appendLine(?string $value): StringBuilder
    {
        $this->s .= PHP_EOL;
        return $this->append($value);
    }

    public function clear(): void
    {
        $this->s = '';
    }

    public function __toString(): string
    {
        return $this->s;
    }
}
