<?php

namespace RavenDB\Extensions;

use Doctrine\Common\Annotations\AnnotationReader;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Session\EntityToJson;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Utils\TimeUtils;
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

    public static function getDefaultEntityMapper(array $normalizers = [], array $encoders = []): EntityMapper
    {
        if (self::$entityMapper == null) {
            self::$entityMapper = self::createDefaultEntityMapper($normalizers, $encoders);
            self::$entityMapper->setPropertyNamingStrategy(PropertyNamingStrategy::none());
        }

        return self::$entityMapper;
    }

    public static function createDefaultEntityMapper(array $normalizers = [], array $encoders = []): EntityMapper
    {
        $dotNetNamingConverter = new DotNetNamingConverter();
        $dotNetNamingConverter->setEnabled(false);

        $allNormalizers = array_merge($normalizers, self::getDefaultNormalizers($dotNetNamingConverter));
        $allEncoders = array_merge($encoders, self::getDefaultEncoders());

        $entityMapper = new EntityMapper($allNormalizers, $allEncoders);
        $entityMapper->setDotNetNamingConvertor($dotNetNamingConverter);

        return $entityMapper;
    }

    private static function getDefaultNormalizers(DotNetNamingConverter $dotNetNamingConverter): array
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));

        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory, $dotNetNamingConverter);

        return [
            new RavenArrayNormalizer(),
            new TypedArrayNormalizer(),
            new StringArrayNormalizer(),
            new ExtendedObjectArrayNormalizer(),
            new ValueObjectNormalizer(),
            new DurationNormalizer(),
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.v0000\Z',
                DateTimeNormalizer::TIMEZONE_KEY => null
            ]),
            new ObjectNormalizer(
                $classMetadataFactory,
                $metadataAwareNameConverter,
                new PropertyAccessor(),
                new ReflectionExtractor()
            ),
            new ArrayDenormalizer()
        ];
    }

    private static function getDefaultEncoders(): array
    {
        return [
            'json' => new JsonEncoder()
        ];
    }

    public static function getDefaultMapper(array $normalizers = [], array $encoders = []): EntityMapper
    {
        if (self::$defaultMapper == null) {
            self::$defaultMapper = self::createDefaultJsonSerializer($normalizers, $encoders);
            self::$defaultMapper->setPropertyNamingStrategy(PropertyNamingStrategy::none());
        }

        return self::$defaultMapper;
    }

    public static function createDefaultJsonSerializer(array $normalizers = [], array $encoders = []): EntityMapper
    {
        return self::createDefaultEntityMapper($normalizers, $encoders);
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

        if ($query->getWaitForNonStaleResultsTimeout() != null) {
            $data["WaitForNonStaleResultsTimeout"] = TimeUtils::durationToTimeSpan($query->getWaitForNonStaleResultsTimeout());
        }

        if ($query->isDisableCaching()) {
            $data["DisableCaching"] = $query->isDisableCaching();
        }

        if ($query->isSkipDuplicateChecking()) {
            $data["SkipDuplicateChecking"] = $query->isSkipDuplicateChecking();
        }

        $data["QueryParameters"] = [];
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

    public static function tryGetConflict(array $metadata): bool
    {
        if (array_key_exists(DocumentsMetadata::CONFLICT, $metadata)) {
            return $metadata[DocumentsMetadata::CONFLICT];
        }
        return false;
    }

    public static function reset(): void
    {
        self::$entityMapper = null;
        self::$defaultMapper = null;
    }
}
