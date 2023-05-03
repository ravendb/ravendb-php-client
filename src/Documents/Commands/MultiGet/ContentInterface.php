<?php

namespace RavenDB\Documents\Commands\MultiGet;

interface ContentInterface
{
    function writeContent(): array;
}
