<?php

namespace RavenDB\Extensions;

use RavenDB\Type\TypedArray;
use RavenDB\Type\TypedMap;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TypedArrayNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface,
    DenormalizerInterface,
    DenormalizerAwareInterface
{
    private ?NormalizerInterface $normalizer = null;
    private ?DenormalizerInterface $denormalizer = null;

    public function setDenormalizer(DenormalizerInterface $denormalizer)
    {
        $this->denormalizer = $denormalizer;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $object = new $type();

        foreach ($data as $key => $item) {
            $itemObject = $this->denormalizer->denormalize($item, $object->getType(), $format, $context);
            $object->offsetSet($key, $itemObject);
        }

        return $object;
    }

    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return is_subclass_of($type, TypedArray::class) || is_subclass_of($type, TypedMap::class);
    }

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        if (count($object) == 0) {
            return null;
        }

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization($data, string $format = null)
    {
        return is_a($data, TypedArray::class) || is_a($data, TypedMap::class);
    }


}
