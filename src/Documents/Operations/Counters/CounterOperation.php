<?php

namespace RavenDB\Documents\Operations\Counters;

use RavenDB\Primitives\SharpEnum;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CounterOperation
{
    #[SerializedName("Type")]
    private ?CounterOperationType $type = null;
    #[SerializedName("CounterName")]
    private ?string $counterName = null;
    #[SerializedName("Delta")]
    private ?int $delta = null;

    #[Ignore]
    protected ?string $changeVector = null;

    public function getType(): ?CounterOperationType
    {
        return $this->type;
    }

    public function setType(?CounterOperationType $type): void
    {
        $this->type = $type;
    }

    public function getCounterName(): ?string
    {
        return $this->counterName;
    }

    public function setCounterName(?string $counterName): void
    {
        $this->counterName = $counterName;
    }

    public function getDelta(): ?int
    {
        return $this->delta;
    }

    public function setDelta(?int $delta): void
    {
        $this->delta = $delta;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function setChangeVector(?string $changeVector): void
    {
        $this->changeVector = $changeVector;
    }

    public static function create(?string $counterName, ?CounterOperationType $type, ?int $delta = null): CounterOperation
    {
        $operation = new CounterOperation();
        $operation->setCounterName($counterName);
        $operation->setType($type);
        $operation->setDelta($delta);
        return $operation;
    }

    public function serialize(): array
    {
        $data = [];

        $data["Type"] = SharpEnum::value($this->type);
        $data["CounterName"] = $this->counterName;
        $data["Delta"] = $this->delta;

        return $data;
    }
}
