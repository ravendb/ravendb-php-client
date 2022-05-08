<?php

namespace RavenDB\Extensions;

use Symfony\Component\Serializer\Serializer;

class EntityMapper extends Serializer
{
    private ?DotNetNamingConverter $dotNetNamingConvertor = null;

    public function __construct(array $normalizers = [], array $encoders = [])
    {
        parent::__construct($normalizers, $encoders);
    }

    public function setPropertyNamingStrategy(PropertyNamingStrategy $strategy): void
    {
        if ($this->dotNetNamingConvertor) {
            $this->dotNetNamingConvertor->setEnabled($strategy->isDotNetNamingStrategy());
        }
    }

    public function setDotNetNamingConvertor(DotNetNamingConverter $dotNetNamingConvertor)
    {
        $this->dotNetNamingConvertor = $dotNetNamingConvertor;
    }

    public function updateValue(object &$entity, array $document)
    {
        $entity = $this->denormalize($document, get_class($entity));
    }
}
