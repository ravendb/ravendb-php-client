<?php

namespace RavenDB\Extensions;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class JsonExtensions
{
    private static ?EntityMapper $entityMapper = null;
    private static ?EntityMapper $defaultMapper = null;

    public static function getDefaultEntityMapper(): EntityMapper
    {
        if (self::$entityMapper == null) {
            self::$entityMapper = self::createDefaultEntityMapper();
        }

        self::$entityMapper->setPropertyNamingStrategy(PropertyNamingStrategy::none());

        return self::$entityMapper;
    }

    public static function createDefaultEntityMapper(): EntityMapper
    {
        $dotNetNamingConvertor = new DotNetNamingConverter();
        $dotNetNamingConvertor->setEnabled(false);


        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory, $dotNetNamingConvertor);

        $normalizers = [
            new TypedArrayNormalizer(),
            new StringArrayNormalizer(),
            new ObjectNormalizer(
                $classMetadataFactory,
                $metadataAwareNameConverter,
                new PropertyAccessor(),
                new ReflectionExtractor()
            ),
            new ArrayDenormalizer(),
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.vZ'
            ])
        ];

        $encoders = [
            'json' => new JsonEncoder()
        ];

        $entityMapper = new EntityMapper($normalizers, $encoders);
        $entityMapper->setDotNetNamingConvertor($dotNetNamingConvertor);
        return $entityMapper;
    }

    public static function getDefaultMapper(): EntityMapper
    {
        if (self::$defaultMapper == null) {
            self::$defaultMapper = self::createDefaultJsonSerializer();
        }

        self::$defaultMapper->setPropertyNamingStrategy(PropertyNamingStrategy::none());

        return self::$defaultMapper;
    }

    public static function createDefaultJsonSerializer(): EntityMapper
    {
        return self::createDefaultEntityMapper();
    }
}
