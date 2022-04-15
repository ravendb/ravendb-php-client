<?php

namespace RavenDB\Extensions;

use RavenDB\Type\StringArray;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class StringArrayNormalizer implements DenormalizerInterface
{
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = new $type();

        if ($data) {
            foreach ($data as $key => $item) {
                $object->offsetSet($key, $item);
            }
        }

        return $object;
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return $type == StringArray::class;
    }
}
