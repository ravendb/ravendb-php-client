<?php

namespace RavenDB\Extensions;

use RavenDB\Type\StringArray;
use RavenDB\Type\StringArrayResult;
use RavenDB\Type\StringSet;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class StringArrayNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerInterface
{

    private ?NormalizerInterface $normalizer = null;

    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        $object = new $type();

        if ($data) {
            foreach ($data as $key => $item) {
                $object->offsetSet($key, $item);
            }
        }

        return $object;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null): bool
    {
        return is_a($type, StringArray::class, true);
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        if (count($object) == 0) {
            return null;
        }

        $result = [];
        foreach ($object as $key => $item) {
            $result[$key] = $this->normalizer->normalize($item, $format, $context);
        }
        return $result;
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return $data instanceof StringArray;
    }
}
