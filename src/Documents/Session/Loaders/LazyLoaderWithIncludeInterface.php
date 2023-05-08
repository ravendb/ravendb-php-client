<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Lazy;

interface LazyLoaderWithIncludeInterface
{
    //TBD expr overrides with expressions + maybe we TInclude, see:

    /**
     * Begin a load while including the specified path
     * @param ?string $path Path in documents in which server should look for a 'referenced' documents.
     * @return LazyLoaderWithIncludeInterface Lazy loader with includes support
     */
    public function include(?string $path): LazyLoaderWithIncludeInterface;

    public function load(?string $className, string|array ...$ids): Lazy;

    public function loadSingle(?string $className, string $id): Lazy;
}
