<?php

namespace RavenDB\Type;

use DS\Map;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\IdTypeAndName;

/**
 * @todo in future we can change how this class works, in order to improve performance
 *       we can concat id, type and name and use the received string as the key
 *       For example, we can do something like:
 *          $key = '$' . $id . '.' . $type . '.' , $name;
 *
 * Note: current implementation is copied from Java solution, but at the end we can change it and
 */
class DeferredCommandsMap
{
    private Map $map;

    public function __construct()
    {
        $this->map = new Map();
    }

    public function clear(): void
    {
        $this->map->clear();
    }

    /**
     * @param IdTypeAndName $key
     * @param ?CommandDataInterface $value The value to be associated with the key.
     */
    public function put(IdTypeAndName $key, ?CommandDataInterface $value)
    {
        $this->map->put($key, $value);
    }

    public function get(IdTypeAndName $key): ?CommandDataInterface
    {
        return $this->map->get($key);
    }

    public function hasKeyWith(string $id, CommandType $type, ?string $name): bool
    {
        return $this->getIndexFor($id, $type, $name) != null;
    }

    public function getValueFor(string $id, CommandType $type, ?string $name): ?object
    {
        $commandIndex = $this->getIndexFor($id, $type, null);
        if ($commandIndex == null) {
            return null;
        }
        return $this->get($commandIndex);
    }

    public function getIndexFor(string $id, CommandType $type, ?string $name): ?IdTypeAndName
    {
        /**
         * @var IdTypeAndName $commandMap
         */
        foreach ($this->map as $commandMap => $command) {
            if ($commandMap->getId() == $id && $commandMap->getType()->equals($type) && $commandMap->getName() == $name) {
                return $commandMap;
            }
        }
        return null;
    }

    public function getIndexOrCreateNewFor(string $id, CommandType $type, ?string $name): IdTypeAndName
    {
        $idTypeName = $this->getIndexFor($id, $type, $name);
        if ($idTypeName != null) {
            return $idTypeName;
        }

        return IdTypeAndName::create($id, $type, $name);
    }
}
