<?php

namespace RavenDB\Documents;

use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Utils\StringUtils;

class IdTypeAndName
{
    private ?string $id = null;
    private ?CommandType $type = null;
    private ?string $name = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getType(): ?CommandType
    {
        return $this->type;
    }

    public function setType(?CommandType $commandType): void
    {
        $this->type = $commandType;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function equals(?object $o): bool
    {
        if ($this == $o) {
            return true;
        }

        if ($o == null) {
            return false;
        }

        if (self::class != get_class($o)) {
            return false;
        }

        /** @var IdTypeAndName $that */
        $that = $o;

        if ($this->id !== $that->getId()) {
            return false;
        }

        if (!$this->type->equals($that->getType())) {
            return false;
        }

        return $this->name == $that->getName();
    }


    public function hashCode(): int
    {
        $result = StringUtils::overflow32(
            $this->id != null ?
                StringUtils::hashCode($this->id) :
                0
        );
        $result = StringUtils::overflow32(
            31 * $result + (
                $this->type != null ?
                StringUtils::hashCode($this->type->getValue()) :
                0
            )
        );
        $result = StringUtils::overflow32(
            31 * $result + (
                $this->name != null ?
                    StringUtils::hashCode($this->name) :
                    0
            )
        );
        return $result;
    }

    public static function create(string $id, CommandType $type, ?string $name): IdTypeAndName
    {
        $idTypeAndName = new IdTypeAndName();
        $idTypeAndName->setId($id);
        $idTypeAndName->setType($type);
        $idTypeAndName->setName($name);
        return $idTypeAndName;
    }
}
