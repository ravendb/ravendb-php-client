<?php

namespace RavenDB\Extensions;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;

class EntityMapper extends Serializer
{
    private ?DotNetNamingConverter $dotNetNamingConvertor = null;
    private array $dynamicNormalizers = [];

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
        $this->denormalize($document, get_class($entity), null, [AbstractNormalizer::OBJECT_TO_POPULATE => $entity]);
    }

    /**
     * @param SerializerAwareInterface|DenormalizerAwareInterface|NormalizerAwareInterface|NormalizerInterface|DenormalizerInterface $normalizer
     */
    public function registerNormalizer($normalizer)
    {

    }
}
