<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;


interface LoaderWithIncludeInterface
{
    //TBD expr overrides with expressions + maybe we TInclude, see:

    /**
     * Includes the specified path.
     * @param string $path Path to include
     * @return LoaderWithIncludeInterface Loader with includes
     */
    function include(string $path): LoaderWithIncludeInterface;


    /**
     * Loads the specified ids.
     * @param string $className Result class
     * @param string|StringArray $ids Ids to load
     * @return ObjectArray|object Map id to entity
     */
    public function load(string $className, ...$ids);
}
