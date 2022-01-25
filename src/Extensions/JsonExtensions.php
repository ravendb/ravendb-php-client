<?php

namespace RavenDB\Extensions;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonExtensions
{
    private static ?Serializer $entityMapper = null;
    private static ?Serializer $defaultMapper = null;

    public static function getDefaultEntityMapper(): Serializer
    {
        if (self::$entityMapper === null) {
            self::$entityMapper = self::createDefaultEntityMapper();
        }

        return self::$entityMapper;
    }

    public static function createDefaultEntityMapper(): Serializer
    {
        //        ObjectMapper objectMapper = new ObjectMapper();
//        objectMapper.disable(DeserializationFeature.FAIL_ON_UNKNOWN_PROPERTIES);
//        objectMapper.configure(JsonParser.Feature.ALLOW_SINGLE_QUOTES, true);
//        objectMapper.setConfig(objectMapper.getSerializationConfig().with(new NetDateFormat()));
//        objectMapper.setConfig(objectMapper.getDeserializationConfig().with(new NetDateFormat()));
//        objectMapper.setAnnotationIntrospector(new SharpAwareJacksonAnnotationIntrospector());
//        return $objectMapper;


        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

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
//                DateTimeNormalizer::FORMAT_KEY => DateTimeInterface::RFC3339_EXTENDED
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.vZ'
            ])
        ];

        $encoders = [
            'json' => new JsonEncoder()
        ];

        return new Serializer($normalizers, $encoders);
    }

    public static function getDefaultMapper(): Serializer
    {
        if (self::$defaultMapper == null) {
            self::$defaultMapper = self::createDefaultJsonSerializer();
        }

        return self::$defaultMapper;
    }

    public static function createDefaultJsonSerializer(): Serializer
    {
//        ObjectMapper objectMapper = new ObjectMapper();
//        objectMapper.setPropertyNamingStrategy(new DotNetNamingStrategy());
//        objectMapper.disable(DeserializationFeature.FAIL_ON_UNKNOWN_PROPERTIES);
//        objectMapper.configure(JsonParser.Feature.ALLOW_SINGLE_QUOTES, true);
//        objectMapper.setConfig(objectMapper.getSerializationConfig().with(new NetDateFormat()));
//        objectMapper.setConfig(objectMapper.getDeserializationConfig().with(new NetDateFormat()));
//        objectMapper.setAnnotationIntrospector(new SharpAwareJacksonAnnotationIntrospector());
//
//        SimpleModule durationModule = new SimpleModule();
//        durationModule.addSerializer(new DurationSerializer());
//        durationModule.addDeserializer(Duration.class, new DurationDeserializer());
//
//        objectMapper.registerModule(durationModule);
//
//        return objectMapper;

        return self::createDefaultEntityMapper();
    }
}
