<?php

namespace RavenDB\Extensions;

use RavenDB\Type\ValueObjectInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ValueObjectNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        if (!empty($data)) {
            return new $type($data);
        }

        return null;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null): bool
    {
        return is_subclass_of($type, ValueObjectInterface::class);
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        return $object->getValue();
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return is_subclass_of($data, ValueObjectInterface::class);
    }
}
