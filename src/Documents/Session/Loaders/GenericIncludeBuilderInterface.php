<?php

namespace RavenDB\Documents\Session\Loaders;

// !status: DONE

/**
 * @template TBuilder
 *
 * @extends DocumentIncludeBuilderInterface<TBuilder>
 * @extends CounterIncludeBuilderInterface<TBuilder>
 * @extends GenericTimeSeriesIncludeBuilderInterface<TBuilder>
 * @extends CompareExchangeValueIncludeBuilderInterface<TBuilder>
 *
 */
interface GenericIncludeBuilderInterface extends
    DocumentIncludeBuilderInterface,
    CounterIncludeBuilderInterface,
    GenericTimeSeriesIncludeBuilderInterface,
    CompareExchangeValueIncludeBuilderInterface
{

}
