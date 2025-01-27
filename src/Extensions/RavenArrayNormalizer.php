<?php

namespace RavenDB\Extensions;

use RavenDB\Documents\Indexes\AdditionalAssemblySet;
use RavenDB\Documents\Indexes\AdditionalSourcesArray;
use RavenDB\Documents\Indexes\IndexConfiguration;
use RavenDB\Documents\Indexes\IndexFieldOptionsArray;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class RavenArrayNormalizer implements
    NormalizerInterface,
    NormalizerAwareInterface
{
    private ?NormalizerInterface $normalizer = null;

    public function setNormalizer(NormalizerInterface $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, ?string $format = null, array $context = [])
    {
        if ($object instanceof AdditionalAssemblySet) {
            if (count($object) == 0) {
                return $object;
            }
        }

        if (($object instanceof AdditionalSourcesArray) ||
            ($object instanceof IndexFieldOptionsArray) ||
            ($object instanceof IndexConfiguration)) {
            if (count($object) == 0) {
                return [];
            }
        }

        $result = [];
        foreach ($object as $key => $item) {
            $result[$key] = $this->normalizer->normalize($item, $format, $context);
        }
        return $result;
    }

    public function supportsNormalization($data, ?string $format = null)
    {
        return
            ($data instanceof AdditionalAssemblySet) ||
            ($data instanceof AdditionalSourcesArray) ||
            ($data instanceof IndexFieldOptionsArray) ||
            ($data instanceof IndexConfiguration);
    }
}
