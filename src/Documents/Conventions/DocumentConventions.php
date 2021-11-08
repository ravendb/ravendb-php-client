<?php

namespace RavenDB\Documents\Conventions;

use InvalidArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\LoadBalanceBehavior;
use RavenDB\Utils\ClassUtils;
use RavenDB\Utils\StringUtils;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Throwable;

class DocumentConventions
{
    protected bool $topologyUpdatesEnabled = false;

    protected bool $disableAtomicDocumentWritesInClusterWideTransaction = false;
    protected int $loadBalancerContextSeed = 0;
    protected bool $frozen = false;

    protected int $maxNumberOfRequestsPerSession;
    protected int $maxHttpCacheSize;

    protected array $idPropertyCache = [];
    protected ?string $identityPartsSeparator = null;

    protected array $cachedDefaultTypeCollectionNames = [];

    protected LoadBalanceBehavior $loadBalanceBehavior;


    protected Serializer $entityMapper;

    public function __construct()
    {
        $this->loadBalanceBehavior = LoadBalanceBehavior::none();

//        _readBalanceBehavior = ReadBalanceBehavior.NONE;
//        _findIdentityProperty = q -> q.getName().equals("id");
        $this->identityPartsSeparator = '/';
//        _findIdentityPropertyNameFromCollectionName = entityName -> "Id";
//        _findJavaClass = (String id, ObjectNode doc) -> {
//            JsonNode metadata = doc.get(Constants.Documents.Metadata.KEY);
//            if (metadata != null) {
//                TextNode javaType = (TextNode) metadata.get(Constants.Documents.Metadata.RAVEN_JAVA_TYPE);
//                if (javaType != null) {
//                    return javaType.asText();
//                }
//            }
//
//            return null;
//        };
//        _findJavaClassName = type -> ReflectionUtil.getFullNameWithoutVersionInformation(type);
//        _findJavaClassByName = name -> {
//            try {
//                return Class.forName(name);
//            } catch (ClassNotFoundException e) {
//                throw new RavenException("Unable to find class by name = " + name, e);
//            }
//        };
//        _transformClassCollectionNameToDocumentIdPrefix =
//          collectionName -> defaultTransformCollectionNameToDocumentIdPrefix(collectionName);
//
//        _findCollectionName = type -> defaultGetCollectionName(type);
//
        $this->maxNumberOfRequestsPerSession = 30;
//        _bulkInsert = new BulkInsertConventions(this);
        $this->maxHttpCacheSize = 128 * 1024 * 1024;

        $this->entityMapper = JsonExtensions::getDefaultEntityMapper();
//
//        _aggressiveCache = new AggressiveCacheConventions(this);
//        _firstBroadcastAttemptTimeout = Duration.ofSeconds(5);
//        _secondBroadcastAttemptTimeout = Duration.ofSeconds(30);
//
//        _waitForIndexesAfterSaveChangesTimeout = Duration.ofSeconds(15);
//        _waitForReplicationAfterSaveChangesTimeout = Duration.ofSeconds(15);
//        _waitForNonStaleResultsTimeout = Duration.ofSeconds(15);
//
//        _sendApplicationIdentifier = true;
    }

    public function getEntityMapper(): Serializer
    {
        return $this->entityMapper;
    }

    public function setEntityMapper(Serializer $mapper): void
    {
        $this->entityMapper = $mapper;
    }


    public function disableTopologyUpdates(): void
    {
        $this->topologyUpdatesEnabled = false;
    }

    public function enableTopologyUpdates(): void
    {
        $this->topologyUpdatesEnabled = true;
    }

    public function isTopologyUpdatesEnabled(): bool
    {
        return $this->topologyUpdatesEnabled;
    }

    public function getDisableAtomicDocumentWritesInClusterWideTransaction(): bool
    {
        return $this->disableAtomicDocumentWritesInClusterWideTransaction;
    }

    public function setDisableAtomicDocumentWritesInClusterWideTransaction(bool $value): void
    {
        $this->disableAtomicDocumentWritesInClusterWideTransaction = $value;
    }

    public function getLoadBalancerContextSeed(): int
    {
        return $this->loadBalancerContextSeed;
    }

    /**
     * @throws IllegalStateException
     */
    public function setLoadBalancerContextSeed(int $seed): void
    {
        $this->assertNotFrozen();
        $this->loadBalancerContextSeed = $seed;
    }

    public function getLoadBalanceBehavior(): LoadBalanceBehavior
    {
        return $this->loadBalanceBehavior;
    }

    /**
     * @throws IllegalStateException
     */
    public function setLoadBalanceBehavior(LoadBalanceBehavior $loadBalanceBehavior): void
    {
        $this->assertNotFrozen();
        $this->loadBalanceBehavior = $loadBalanceBehavior;
    }

    public function getLoadBalancerPerSessionContextSelector(): ?int
    {
        //todo: change return type and implement this function here
        return null;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    /**
     * @throws IllegalStateException
     */
    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw new IllegalStateException(
                "Conventions has been frozen after documentStore.initialize() " .
                "and no changes can be applied to them."
            );
        }
    }

    public function getMaxNumberOfRequestsPerSession(): int
    {
        return $this->maxNumberOfRequestsPerSession;
    }

    /**
     * @throws IllegalStateException
     */
    public function setMaxNumberOfRequestsPerSession(int $maxNumberOfRequestsPerSession): void
    {
        $this->assertNotFrozen();
        $this->maxNumberOfRequestsPerSession = $maxNumberOfRequestsPerSession;
    }

    public function getMaxHttpCacheSize(): int
    {
        return $this->maxHttpCacheSize;
    }

    /**
     * @throws IllegalStateException
     */
    public function setMaxHttpCacheSize(int $maxHttpCacheSize): void
    {
        $this->assertNotFrozen();
        $this->maxHttpCacheSize = $maxHttpCacheSize;
    }

    /**
     *  Gets the identity property.
     */
    public function getIdentityProperty(string $className): string
    {

        if (array_key_exists($className, $this->idPropertyCache)) {
            $info = $this->idPropertyCache[$className];
            if ($info !== null) {
                return $info;
            }
        }


        try {
            $idField = '';

            // todo: Implement this!!!!
  //          Field idField = Arrays.stream(Introspector.getBeanInfo(clazz).getPropertyDescriptors())
  //                    .filter(x -> _findIdentityProperty.apply(x))
  //                    .findFirst()
  //                    .map(x -> getField(clazz, x.getName()))
  //                    .orElse(null);

            $this->idPropertyCache[$className] = $idField;

            return $idField;
        } catch (Throwable $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    public function getIdentityPartsSeparator(): string
    {
        return $this->identityPartsSeparator;
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function setIdentityPartsSeparator(string $identityPartsSeparator): void
    {
        $this->assertNotFrozen();

        if ($identityPartsSeparator == '|') {
            throw new InvalidArgumentException("Cannot set identity parts separator to '|'");
        }

        $this->identityPartsSeparator = $identityPartsSeparator;
    }

    /**
     * @throws IllegalStateException
     */
    private function defaultGetCollectionName(string $className): string
    {
        $result = null;
        if (array_key_exists($className, $this->cachedDefaultTypeCollectionNames)) {
            $result = $this->cachedDefaultTypeCollectionNames[$className];

            if ($result != null) {
                return $result;
            }
        }

        // we want to reject queries and other operations on abstract types, because you usually
        // want to use them for polymorphic queries, and that require the conventions to be
        // applied properly, so we reject the behavior and hint to the user explicitly
        if (StringUtils::endsWith('Interface', $className)) {
            throw new IllegalStateException(
                "Cannot find collection name for interface " . $className .
                ", only concrete classes are supported. Did you forget to customize conventions.findCollectionName?"
            );
        }

        // @todo: implement following code.
        // This was skipped because I don't know how to test is class abstract or not, I'm just passing className here.
//        if (Modifier.isAbstract(clazz.getModifiers())) {
//            throw new IllegalStateException(
//              "Cannot find collection name for abstract class " + clazz.getName() +
//              ", only concrete class are supported. Did you forget to customize conventions.findCollectionName?"
//            );
//        }

        $result = StringUtils::pluralize(ClassUtils::getSimpleClassName($className));

        $this->cachedDefaultTypeCollectionNames[$className] = $result;

        return $result;
    }

    /**
     * @throws IllegalStateException
     */
    public function getCollectionNameForClass(string $className): string
    {
        // @todo: implement _findCollectionName call and function saving
        $collectionName = null;//$this->_findCollectionName.apply(clazz);

        if ($collectionName != null) {
            return $collectionName;
        }

        return $this->defaultGetCollectionName($className);
    }

    /**
     * @throws IllegalStateException
     */
    public function getCollectionName(?object $entity): ?string
    {
        if ($entity == null) {
            return null;
        }

        return $this->getCollectionNameForClass(get_class($entity));
    }
}
