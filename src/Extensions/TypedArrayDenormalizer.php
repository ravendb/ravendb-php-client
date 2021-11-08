<?php

namespace RavenDB\Extensions;

use RavenDB\Type\TypedArray;
use RavenDB\Type\TypedMap;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TypedArrayDenormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = new $type();
        $object->denormalize($this, $data, $format, $context);

        return $object;
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_subclass_of($type, TypedArray::class) || is_subclass_of($type, TypedMap::class);
    }
}
