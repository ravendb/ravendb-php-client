<?php

namespace RavenDB\Documents\TimeSeries;

use _PHPStan_b8e553790\Symfony\Component\Console\Exception\InvalidOptionException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Operations\TimeSeries\ConfigureRawTimeSeriesPolicyOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesPolicyOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesValueNamesOperation;
use RavenDB\Documents\Operations\TimeSeries\ConfigureTimeSeriesValueNamesParameters;
use RavenDB\Documents\Operations\TimeSeries\RawTimeSeriesPolicy;
use RavenDB\Documents\Operations\TimeSeries\RemoveTimeSeriesPolicyOperation;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesPolicy;
use RavenDB\Documents\Session\TimeSeries\TimeSeriesValuesHelper;
use RavenDB\Primitives\TimeValue;
use RavenDB\Type\StringArray;

class
TimeSeriesOperations
{
    private ?DocumentStoreInterface $store = null;
    private ?string $database = null;
    private ?MaintenanceOperationExecutor $executor = null;

    public function __construct(DocumentStoreInterface $store, ?string $database = null)
    {
        $this->store = $store;

        if ($database == null) {
            $database = $store->getDatabase();
        }

        $this->database = $database;
        $this->executor = $store->maintenance()->forDatabase($database);
    }

    public function register(
        string                        $collectionClassOrCollection, //: ClassConstructor<any> | string,
        string                        $timeSeriesEntryClassOrName, //: string | ClassConstructor<any>,
        null|string|StringArray|array $nameOrValuesName = null
    ): void
    {
        if (is_array($nameOrValuesName)) {
            $nameOrValuesName = StringArray::fromArray($nameOrValuesName);
        }

        if (!class_exists($collectionClassOrCollection)) {
            $this->registerInternal($collectionClassOrCollection, $timeSeriesEntryClassOrName, $nameOrValuesName);
            return;
        }

        $collectionClass = $collectionClassOrCollection;

        if (!class_exists($timeSeriesEntryClassOrName)) {
            $findCollectionName = $this->store->getConventions()->getFindCollectionName();
            $collection = $findCollectionName($collectionClass);
            $this->registerInternal($collection, $timeSeriesEntryClassOrName, $nameOrValuesName);
            return;
        }

        // [ClassConstructor<TCollection>, ClassConstructor<TTimeSeriesEntry>, string?]
        $name = $nameOrValuesName;
        if (empty($name)) {
            $name = TimeSeriesOperations::getTimeSeriesName($timeSeriesEntryClassOrName, $this->store->getConventions());
        }

        $mapping = TimeSeriesValuesHelper::getFieldsMapping($timeSeriesEntryClassOrName);
        if (empty($mapping)) {
            throw new InvalidOptionException(TimeSeriesOperations::getTimeSeriesName($timeSeriesEntryClassOrName, $this->store->getConventions()) . " must contain valid mapping");
        }

        $findCollectionName = $this->store->getConventions()->getFindCollectionName();
        $collection = $findCollectionName($collectionClass);

        $valueNames = StringArray::fromArray($mapping);
        $this->registerInternal($collection, $name, $valueNames);

    }

    private function registerInternal(string $collection, string $name, StringArray $valueNames): void
    {
        $parameters = new ConfigureTimeSeriesValueNamesParameters();
        $parameters->setCollection($collection);
        $parameters->setTimeSeries($name);
        $parameters->setValueNames($valueNames);
        $parameters->setUpdate(true);
        $command = new ConfigureTimeSeriesValueNamesOperation($parameters);
        $this->executor->send($command);
    }

//    /**
//     * Register value names of a time-series
//     * @param collectionClass Collection class
//     * @param timeSeriesEntryClass Time-series entry class
//     * @param <TCollection> Collection class
//     * @param <TTimeSeriesEntry> Time-series entry class
//     */
//    public <TCollection, TTimeSeriesEntry> void register(Class<TCollection> collectionClass, Class<TTimeSeriesEntry> timeSeriesEntryClass) {
//        register(collectionClass, timeSeriesEntryClass, null);
//    }
//
//    /**
//     * Register value names of a time-series
//     * @param collectionClass Collection class
//     * @param timeSeriesEntryClass Time-series entry class
//     * @param name Override time series entry name
//     * @param <TCollection> Collection class
//     * @param <TTimeSeriesEntry> Time-series entry class
//     */
//    public <TCollection, TTimeSeriesEntry> void register(Class<TCollection> collectionClass, Class<TTimeSeriesEntry> timeSeriesEntryClass, String name) {
//        if (name == null) {
//            name = getTimeSeriesName(timeSeriesEntryClass, _store.getConventions());
//        }
//
//        SortedMap<Byte, Tuple<Field, String>> mapping = TimeSeriesValuesHelper.getFieldsMapping(timeSeriesEntryClass);
//        if (mapping == null) {
//            throw new IllegalStateException(getTimeSeriesName(timeSeriesEntryClass, _store.getConventions()) + " must contain " + TimeSeriesValue.class.getSimpleName());
//        }
//
//        String collection = _store.getConventions().getFindCollectionName().apply(collectionClass);
//        String[] valueNames = mapping
//                .values()
//                .stream()
//                .map(x -> x.second)
//                .toArray(String[]::new);
//        register(collection, name, valueNames);
//    }
//
//    /**
//     * Register value name of a time-series
//     * @param collectionClass Collection class
//     * @param name Time series name
//     * @param valueNames Values to register
//     * @param <TCollection> Collection class
//     */
//    public <TCollection> void register(Class<TCollection> collectionClass, String name, String[] valueNames) {
//        String collection = _store.getConventions().getFindCollectionName().apply(collectionClass);
//        register(collection, name, valueNames);
//    }
//
//    /**
//     * Register value names of a time-series
//     * @param collection Collection name
//     * @param name Time series name
//     * @param valueNames Values to register
//     */
//    public void register(String collection, String name, String[] valueNames) {
//        ConfigureTimeSeriesValueNamesOperation.Parameters parameters = new ConfigureTimeSeriesValueNamesOperation.Parameters();
//        parameters.setCollection(collection);
//        parameters.setTimeSeries(name);
//        parameters.setValueNames(valueNames);
//        parameters.setUpdate(true);
//
//        ConfigureTimeSeriesValueNamesOperation command = new ConfigureTimeSeriesValueNamesOperation(parameters);
//        _executor.send(command);
//    }

    /**
     * Set rollup and retention policy
     *
     * @param string $collectionClassOrCollection
     * @param string $name
     * @param TimeValue|null $aggregation
     * @param TimeValue|null $retention
     */
    public function setPolicy(
        string    $collectionClassOrCollection,
        string    $name,
        ?TimeValue $aggregation = null,
        ?TimeValue $retention = null
    ): void
    {
        $collection = $collectionClassOrCollection;
        if (class_exists($collectionClassOrCollection)) {
            $f = $this->store->getConventions()->getFindCollectionName();
            $collection = $f($collectionClassOrCollection);
        }

        $p = new TimeSeriesPolicy($name, $aggregation, $retention);
        $this->executor->send(new ConfigureTimeSeriesPolicyOperation($collection, $p));
    }

    /**
     * Set raw retention policy
     */
    public function setRawPolicy(
        string    $collectionClassOrCollection,
        ?TimeValue $retention = null
    ): void {
        $collection = $collectionClassOrCollection;
        if (class_exists($collectionClassOrCollection)) {
            $f = $this->store->getConventions()->getFindCollectionName();
            $collection = $f($collectionClassOrCollection);
        }

        $p = new RawTimeSeriesPolicy($retention);
        $this->executor->send(new ConfigureRawTimeSeriesPolicyOperation($collection, $p));

    }

    /**
     * Remove policy
     */
    public function removePolicy(string $collectionClassOrCollection, string $name): void
    {
        $collection = $collectionClassOrCollection;
        if (class_exists($collectionClassOrCollection)) {
            $f = $this->store->getConventions()->getFindCollectionName();
            $collection = $f($collectionClassOrCollection);
        }

        $this->executor->send(new RemoveTimeSeriesPolicyOperation($collection, $name));
    }

    public static function getTimeSeriesName(string $className, ?DocumentConventions $conventions): string
    {
        $f = $conventions->getFindCollectionName();
        return $f($className);
    }

    public function forDatabase(?string $database): TimeSeriesOperations
    {
        if (strcasecmp($database, $this->database) == 0) {
            return $this;
        }

        return new TimeSeriesOperations($this->store, $database);
    }
}
