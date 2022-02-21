<?php

namespace RavenDB\Primitives;

interface CleanCloseable
{
    public function close(): void;
}
