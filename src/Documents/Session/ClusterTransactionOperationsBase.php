<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\StringSet;
use RavenDB\Type\StringArray;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Operations\CompareExchangeSessionValue;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueMap;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueState;
use RavenDB\Documents\Operations\CompareExchange\GetCompareExchangeValueOperation;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueResultParser;
use RavenDB\Documents\Operations\CompareExchange\GetCompareExchangeValuesOperation;

class ClusterTransactionOperationsBase
{
    protected ?DocumentSession $session = null;
    private array $state = [];

    public function __construct(?DocumentSession $session)
    {
        if (!$session->getTransactionMode()->isClusterWide()) {
            throw new IllegalStateException("This function is part of cluster transaction session, in order to use it you have to open the Session with ClusterWide option.");
        }

        $this->session = $session;
    }

    public function getSession(): DocumentSession
    {
        return $this->session;
    }

    public function getNumberOfTrackedCompareExchangeValues(): int
    {
        return count($this->state);
    }

    public function isTracked(?string $key): bool
    {
        return $this->tryGetCompareExchangeValueFromSession($key) != null;
    }

    public function & createCompareExchangeValue(?string $key, $item): CompareExchangeValue
    {
        if ($key == null) {
            throw new IllegalArgumentException("Key cannot be null");
        }

        $sessionValueRef = $this->tryGetCompareExchangeValueFromSession($key);
        if ($sessionValueRef == null) {
            $sessionValueRef   = new CompareExchangeSessionValue($key, 0, CompareExchangeValueState::none());
            $this->state[$key] = $sessionValueRef;
        }

        return $sessionValueRef->create($item);
    }

    /**
     * @template T
     *
     * @param CompareExchangeValue<T>|string|null $keyOrItem
     * @param ?int                                $index
     */
    function deleteCompareExchangeValue($keyOrItem, ?int $index = null): void
    {
        if ($keyOrItem instanceof CompareExchangeValue) {
            $this->deleteCompareExchangeValueByValue($keyOrItem);
            return;
        }

        $this->deleteCompareExchangeValueByKey($keyOrItem, $index);
    }

    /**
     * @template T
     *
     * @param CompareExchangeValue<T> $item
     */
    private function deleteCompareExchangeValueByValue(CompareExchangeValue $item): void
    {
        $this->deleteCompareExchangeValueByKey($item->getKey(), $item->getIndex());
    }

    private function deleteCompareExchangeValueByKey(?string $key, int $index): void
    {
        if ($key == null) {
            throw new IllegalArgumentException("Key cannot be null");
        }

        $sessionValueReference = $this->tryGetCompareExchangeValueFromSession($key);
        if ($sessionValueReference == null) {
            $sessionValueReference = new CompareExchangeSessionValue($key, 0, CompareExchangeValueState::none());
            $this->state[$key]     = $sessionValueReference;
        }

        $sessionValueReference->delete($index);
    }

    public function clear(): void
    {
        $this->state = [];
    }

    protected function getCompareExchangeValueInternal(?string $className, ?string $key = null): ?CompareExchangeValue
    {
        $notTrackedReference = false;
        $v                   = $this->getCompareExchangeValueFromSessionInternal($className, $key, $notTrackedReference);
        if (!$notTrackedReference) {
            return $v;
        }

        $this->session->incrementRequestCount();

        $value = $this->session->getOperations()->send(
            new GetCompareExchangeValueOperation(null, $key, false),
            $this->session->getSessionInfo()
        );

        if ($value == null) {
            $this->registerMissingCompareExchangeValue($key);
            return null;
        }

        $sessionValue = $this->registerCompareExchangeValue($value);
        if ($sessionValue != null) {
            return $sessionValue->getValue($className, $this->session->getConventions());
        }

        return null;
    }

    /**
     * @param string                   $className
     * @param array|string|StringArray $keysOrStartsWith
     * @param int                      $start
     * @param int                      $pageSize
     *
     * @return CompareExchangeValueMap
     */
    public function getCompareExchangeValuesInternal(string $className, array|string|StringArray $keysOrStartsWith, int $start = 0, int $pageSize = 25): CompareExchangeValueMap
    {
        if (!is_string($keysOrStartsWith)) {
            return $this->getCompareExchangeValuesInternalByKeys($className, $keysOrStartsWith);
        }

        return $this->getCompareExchangeValuesInternalByPagination($className, $keysOrStartsWith, $start, $pageSize);
    }

    protected function getCompareExchangeValuesInternalByKeys(string $className, $keys): CompareExchangeValueMap
    {
        $notTrackedKeys = new StringSet();
        $results        = $this->getCompareExchangeValuesFromSessionInternal($className, $keys, $notTrackedKeys);

        if ($notTrackedKeys->isEmpty()) {
            return $results;
        }

        $this->session->incrementRequestCount();

        $keysArray = $notTrackedKeys->getArrayCopy();
        $values    = $this->session->getOperations()->send(new GetCompareExchangeValuesOperation($className, $keysArray), $this->session->getSessionInfo());

        foreach ($keysArray as $key) {

            /** @var CompareExchangeValue $value */
            if (!$values->offsetExists($key) || $values->offsetGet($key) == null) {
                $this->registerMissingCompareExchangeValue($key);
                $results[$key] = null;
                continue;
            }
            $value = $values[$key];

            $sessionValue              = $this->registerCompareExchangeValue($value);
            $results[$value->getKey()] = $sessionValue->getValue($className, $this->session->getConventions());
        }

        return $results;
    }

    protected function getCompareExchangeValuesInternalByPagination(string $className, ?string $startsWith, int $start, int $pageSize): CompareExchangeValueMap
    {
        $this->session->incrementRequestCount();

        $values = $this->session->getOperations()->send(
            new GetCompareExchangeValuesOperation(null, $startsWith, $start, $pageSize), $this->session->getSessionInfo()
        );

        $results = new CompareExchangeValueMap();
        /**
         * @var string               $key
         * @var CompareExchangeValue $value
         */
        foreach ($values as $key => $value) {
            if ($value == null) {
                $this->registerMissingCompareExchangeValue($key);
                $results->offsetSet($key, null);
                continue;
            }

            $sessionValue = $this->registerCompareExchangeValue($value);
            $results->offsetSet($key, $sessionValue->getValue($className, $this->session->getConventions()));
        }

        return $results;
    }

    public function getCompareExchangeValueFromSessionInternal(?string $className, ?string $key, bool &$notTracked): ?CompareExchangeValue
    {
        $sessionValueReference = $this->tryGetCompareExchangeValueFromSession($key);
        if ($sessionValueReference != null) {
            $notTracked = false;
            return $sessionValueReference->getValue($className, $this->session->getConventions());
        }

        $notTracked = true;
        return null;
    }

    public function getCompareExchangeValuesFromSessionInternal(string $className, $keys, StringSet &$notTrackedKeys): CompareExchangeValueMap
    {
        $notTrackedKeys->clear();

        $results = new CompareExchangeValueMap();

        if (empty($keys)) {
            return $results;
        }

        foreach ($keys as $key) {
            $sessionValueRef = $this->tryGetCompareExchangeValueFromSession($key);
            if ($sessionValueRef != null) {
                $results[$key] = $sessionValueRef->getValue($className, $this->session->getConventions());
                continue;
            }

            $notTrackedKeys->append($key);
        }

        return $results;
    }

    public function registerMissingCompareExchangeValue(?string $key): CompareExchangeSessionValue
    {
        $value = new CompareExchangeSessionValue($key, -1, CompareExchangeValueState::missing());
        if ($this->session->noTracking) {
            return $value;
        }

        $this->state[$key] = $value;
        return $value;
    }

    public function registerCompareExchangeValues(?array $values): void
    {
        if ($this->session->noTracking) {
            return;
        }

        if ($values != null) {
            foreach ($values as $value) {
                $this->registerCompareExchangeValue(
                    CompareExchangeValueResultParser::getSingleValue(
                        null,
                        $value,
                        false,
                        $this->session->getConventions()
                    )
                );
            }
        }
    }

    public function registerCompareExchangeValue(CompareExchangeValue $value): CompareExchangeSessionValue
    {
        if ($this->session->noTracking) {
            return new CompareExchangeSessionValue($value);
        }
        $sessionValue = null;
        if (array_key_exists($value->getKey(), $this->state)) {
            $sessionValue = $this->state[$value->getKey()];
        }

        if ($sessionValue == null) {
            $sessionValue                  = new CompareExchangeSessionValue($value);
            $this->state[$value->getKey()] = $sessionValue;
            return $sessionValue;
        }

        $sessionValue->updateValue($value, $this->session->getConventions()->getEntityMapper());

        return $sessionValue;
    }

    private function tryGetCompareExchangeValueFromSession(?string $key): ?CompareExchangeSessionValue
    {
        if (!array_key_exists($key, $this->state)) {
            return null;
        }
        return $this->state[$key];
    }

    public function prepareCompareExchangeEntities(SaveChangesData $result): void
    {
        if (empty($this->state)) {
            return;
        }

        /**
         * @var string                      $key
         * @var CompareExchangeSessionValue $value
         */
        foreach ($this->state as $key => $value) {
            $command = $value->getCommand($this->session->getConventions());
            if ($command == null) {
                continue;
            }

            $result->addSessionCommand($command);
        }
    }

    public function updateState(?string $key, int $index): void
    {
        $sessionValueReference = $this->tryGetCompareExchangeValueFromSession($key);

        if ($sessionValueReference == null) {
            return;
        }

        $sessionValueReference->updateState($index);
    }
}
