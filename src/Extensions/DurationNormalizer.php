<?php

namespace RavenDB\Extensions;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DurationNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($object, ?string $format = null, array $context = [])
    {
        return $object->toString();
    }

    public function supportsNormalization($data, ?string $format = null): bool
    {
        return is_a($data, Duration::class);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = [])
    {
        return Duration::fromString($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null)
    {
        return is_a($type, Duration::class, true);
    }
}
