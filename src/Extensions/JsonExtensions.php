<?php

namespace RavenDB\Extensions;

use Doctrine\Common\Annotations\AnnotationReader;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Session\EntityToJson;
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
            new ValueObjectNormalizer(),
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.v'
            ]),
            new ObjectNormalizer(
                $classMetadataFactory,
                $metadataAwareNameConverter,
                new PropertyAccessor(),
                new ReflectionExtractor()
            ),
            new ArrayDenormalizer()
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


    public static function writeIndexQuery(DocumentConventions $conventions, IndexQuery $query): array
    {
        $data = [];

        $data['Query'] = $query->getQuery();
        if ($query->isPageSizeSet() && $query->getPageSize() >= 0) {
            $data['PageSize'] = $query->getPageSize();
        }

        if ($query->isWaitForNonStaleResults()) {
            $data["WaitForNonStaleResults"] = $query->isWaitForNonStaleResults();
        }

        if ($query->getStart() > 0) {
            $data["Start"] = $query->getStart();
        }

//        if ($query->getWaitForNonStaleResultsTimeout() != null) {
//            $data["WaitForNonStaleResultsTimeout"] = TimeUtils::durationToTimeSpan($query->getWaitForNonStaleResultsTimeout()));
//        }

        if ($query->isDisableCaching()) {
            $data["DisableCaching"] = $query->isDisableCaching();
        }

        if ($query->isSkipDuplicateChecking()) {
            $data["SkipDuplicateChecking"] = $query->isSkipDuplicateChecking();
        }

        $data["QueryParameters"] = null;
        if ($query->getQueryParameters() != null) {
            if ($query->getQueryParameters()->isNotEmpty()) {
                $data["QueryParameters"] = EntityToJson::convertEntityToJsonStatic($query->getQueryParameters(), $conventions, null);
            }
        }

        if ($query->getProjectionBehavior() != null && !$query->getProjectionBehavior()->isDefault()) {
            $data["ProjectionBehavior"] = $query->getProjectionBehavior()->getValue();
        }

        return $data;
    }
}
