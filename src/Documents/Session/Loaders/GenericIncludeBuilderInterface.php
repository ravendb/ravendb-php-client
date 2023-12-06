<?php

namespace RavenDB\Documents\Session\Loaders;

/**
 * @template TBuilder
 *
 * @extends DocumentIncludeBuilderInterface<TBuilder>
 * @extends CounterIncludeBuilderInterface<TBuilder>
 * @extends GenericTimeSeriesIncludeBuilderInterface<TBuilder>
 * @extends CompareExchangeValueIncludeBuilderInterface<TBuilder>
 * @extends GenericRevisionIncludeBuilderInterface<TBuilder>
 *
 */
interface GenericIncludeBuilderInterface extends
    DocumentIncludeBuilderInterface,
    CounterIncludeBuilderInterface,
    GenericTimeSeriesIncludeBuilderInterface,
    CompareExchangeValueIncludeBuilderInterface,
    GenericRevisionIncludeBuilderInterface
{

}
