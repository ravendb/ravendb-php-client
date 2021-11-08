<?php

namespace RavenDB\primitives;

interface CleanCloseable
{
    public function close(): void;
}
