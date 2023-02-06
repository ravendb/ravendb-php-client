<?php

namespace RavenDB\Type;

class TypedArray extends ExtendedArrayObject implements TypedArrayInterface
{
    protected string $type;

    protected function __construct(string $type)
    {
        $this->type = $type;

        if (!class_exists($type) && !interface_exists($type)) {
            throw new \TypeError(
                sprintf("Typed array cant be instantiated. Class or interface: >> %s <<  does not exists! ", $this->type)
            );
        }

        parent::__construct();
    }

    public static function forType(string $type): self
    {
        return new self($type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public static function fromArray(array $data, bool $nullAllowed = false): static
    {
        $sa = new static();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $type = $sa->getType();
            if ($value instanceof $type) {
                $sa->offsetSet($key, $value);
            } else {
                $obj = new ($sa->getType())($value);
                $sa->offsetSet($key, $obj);
            }
        }

        return $sa;
    }

    protected function isValueValid($value): bool
    {
        if ($this->isNullAllowed() && $value == null) {
            return true;
        }

        return $value instanceof $this->type;
    }

    protected function getInvalidValueMessage($value): string
    {
        return sprintf("Only values of type %s are supported", $this->type);
    }
}
