<?php

namespace RavenDB\Extensions;

use RavenDB\Type\Duration;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DurationNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = [])
    {
        return $object->toString();
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return is_a($data, Duration::class);
    }
}
