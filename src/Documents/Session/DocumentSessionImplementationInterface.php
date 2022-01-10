<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;

interface DocumentSessionImplementationInterface extends DocumentSessionInterface, EagerSessionOperationsInterface
{
    // @todo: implement this interface

//     DocumentConventions getConventions();
//
//    <T> Map<String, T> loadInternal(Class<T> clazz, String[] ids, String[] includes);
//
//    <T> Map<String, T> loadInternal(Class<T> clazz, String[] ids, String[] includes, String[] counterIncludes);
//
//    <T> Map<String, T> loadInternal(Class<T> clazz, String[] ids, String[] includes, String[] counterIncludes,
//                                    boolean includeAllCounters);
//
//    <T> Map<String, T> loadInternal(Class<T> clazz, String[] ids, String[] includes, String[] counterIncludes,
//                                    boolean includeAllCounters, List<AbstractTimeSeriesRange> timeSeriesIncludes);
//
//    <T> Map<String, T> loadInternal(Class<T> clazz, String[] ids, String[] includes, String[] counterIncludes,
//                                    boolean includeAllCounters, List<AbstractTimeSeriesRange> timeSeriesIncludes,
//                                    String[] compareExchangeValueIncludes);
//
//    <T> Lazy<Map<String, T>> lazyLoadInternal(Class<T> clazz, String[] ids, String[] includes, Consumer<Map<String, T>> onEval);
}
