<?php

namespace tests\RavenDB\Test\Client\_CustomSerializationTest;

use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CurrencyNormalizer implements NormalizerInterface, DenormalizerInterface
{

    public function normalize($object, string $format = null, array $context = [])
    {
        return $object->__toString();
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Money;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $s = explode(' ', $data);

        $money = new Money();
        $money->setAmount(intval($s[0]));
        $money->setCurrency(count($s) > 1 ? strval($s[1]) : null);

        return $money;
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_a($type, Money::class);
    }
}
