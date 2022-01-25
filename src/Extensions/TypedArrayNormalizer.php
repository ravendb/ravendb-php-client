<?php

namespace RavenDB\Extensions;

use RavenDB\Type\TypedArray;
use RavenDB\Type\TypedMap;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TypedArrayNormalizer implements DenormalizerInterface, DenormalizerAwareInterface
{
    private ?DenormalizerInterface $denormalizer;

    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = new $type();

        foreach ($data as $key => $item) {
            $itemObject = $this->denormalizer->denormalize($item, $object->getType());
            $object->offsetSet($key, $itemObject);
        }

        return $object;
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_subclass_of($type, TypedArray::class) || is_subclass_of($type, TypedMap::class);
    }
}
