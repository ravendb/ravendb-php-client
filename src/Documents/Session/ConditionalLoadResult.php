<?php

namespace RavenDB\Documents\Session;

/**
 * @template T
 */
class ConditionalLoadResult
{
    /** @var T $entity */
    private $entity;
    private ?string $changeVector;

    private function __construct()
    {

    }

    /**
     * @return T
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public static function create($entity, ?string $changeVector): ConditionalLoadResult
    {
        $result = new ConditionalLoadResult();
        $result->entity = $entity;
        $result->changeVector = $changeVector;
        return $result;
    }
}
