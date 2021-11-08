<?php

namespace RavenDB\primitives;

interface CleanCloseable
{
    function close(): void;
}
