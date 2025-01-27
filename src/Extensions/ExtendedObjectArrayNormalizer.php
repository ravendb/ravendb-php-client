<?php

namespace RavenDB\Extensions;

use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\TypedArray;
use RavenDB\Type\TypedMap;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ExtendedObjectArrayNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerInterface
//    DenormalizerAwareInterface
{
    private ?NormalizerInterface $normalizer = null;
//    private ?DenormalizerInterface $denormalizer = null;

//    public function setDenormalizer(DenormalizerInterface $denormalizer)
//    {
//        $this->denormalizer = $denormalizer;
//    }

    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        $object = new $type();

        if ($data) {
            foreach ($data as $key => $item) {
//                $itemObject = $this->denormalizer->denormalize($item, $object->getType(), $format, $context);
                $object->offsetSet($key, $item);
            }
        }

        return $object;
    }

    public function supportsDenormalization($data, string $type, ?string $format = null): bool
    {
        return is_a($type, ExtendedArrayObject::class);
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
        return is_a($data, ExtendedArrayObject::class);
    }
}
