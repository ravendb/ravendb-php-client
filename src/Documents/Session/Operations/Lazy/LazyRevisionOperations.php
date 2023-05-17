<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\LazyRevisionsOperationsInterface;

class LazyRevisionOperations implements LazyRevisionsOperationsInterface
{
    protected ?DocumentSession $delegate = null;

    public function __construct(?DocumentSession $delegate)
    {
        $this->delegate = $delegate;
    }

//    public <T> Lazy<T> get(Class<T> clazz, String changeVector) {
//        GetRevisionOperation operation = new GetRevisionOperation(delegate, changeVector);
//        LazyRevisionOperation<T> lazyRevisionOperation = new LazyRevisionOperation<>(clazz, operation, LazyRevisionOperation.Mode.SINGLE);
//        return delegate.addLazyOperation(clazz, lazyRevisionOperation, null);
//    }
//
//    @Override
//    public Lazy<List<MetadataAsDictionary>> getMetadataFor(String id) {
//        return getMetadataFor(id, 0, 25);
//    }
//
//    @Override
//    public Lazy<List<MetadataAsDictionary>> getMetadataFor(String id, int start) {
//        return getMetadataFor(id, start, 25);
//    }
//
//    @SuppressWarnings("unchecked")
//    @Override
//    public Lazy<List<MetadataAsDictionary>> getMetadataFor(String id, int start, int pageSize) {
//        GetRevisionOperation operation = new GetRevisionOperation(delegate, id, start, pageSize);
//        LazyRevisionOperation<MetadataAsDictionary> lazyRevisionOperation = new LazyRevisionOperation<>(MetadataAsDictionary.class, operation, LazyRevisionOperation.Mode.LIST_OF_METADATA);
//        return delegate.addLazyOperation((Class<List<MetadataAsDictionary>>)(Class< ? >)List.class, lazyRevisionOperation, null);
//    }
//
//    @SuppressWarnings("unchecked")
//    @Override
//    public <T> Lazy<Map<String, T>> get(Class<T> clazz, String[] changeVectors) {
//        GetRevisionOperation operation = new GetRevisionOperation(delegate, changeVectors);
//        LazyRevisionOperation<T> lazyRevisionOperation = new LazyRevisionOperation<>(clazz, operation, LazyRevisionOperation.Mode.MAP);
//        return delegate.addLazyOperation((Class<Map<String, T>>)(Class< ? >)Map.class, lazyRevisionOperation, null);
//    }
//
//    @Override
//    public <T> Lazy<T> get(Class<T> clazz, String id, Date date) {
//        GetRevisionOperation operation = new GetRevisionOperation(delegate, id, date);
//        LazyRevisionOperation<T> lazyRevisionOperation = new LazyRevisionOperation<>(clazz, operation, LazyRevisionOperation.Mode.SINGLE);
//        return delegate.addLazyOperation(clazz, lazyRevisionOperation, null);
//    }
//
//    @Override
//    public <T> Lazy<List<T>> getFor(Class<T> clazz, String id) {
//        return getFor(clazz, id, 0, 25);
//    }
//
//    @Override
//    public <T> Lazy<List<T>> getFor(Class<T> clazz, String id, int start) {
//        return getFor(clazz, id, start, 25);
//    }
//
//    @SuppressWarnings("unchecked")
//    @Override
//    public <T> Lazy<List<T>> getFor(Class<T> clazz, String id, int start, int pageSize) {
//        GetRevisionOperation operation = new GetRevisionOperation(delegate, id, start, pageSize);
//        LazyRevisionOperation<T> lazyRevisionOperation = new LazyRevisionOperation<>(clazz, operation, LazyRevisionOperation.Mode.MULTI);
//        return delegate.addLazyOperation((Class<List<T>>)(Class< ? >)List.class, lazyRevisionOperation, null);
//    }
}
