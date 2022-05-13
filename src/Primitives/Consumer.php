<?php

namespace RavenDB\Primitives;

/**
 * @template T
 */
interface Consumer
{
    /**
     * @param T $mixed
     */
    public function accept($mixed): void;
}
