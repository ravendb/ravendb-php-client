<?php

namespace RavenDB\Documents\Session;

// !status: DONE
abstract class MethodCall
{
    public array $args = [];
    public ?string $accessPath = null;
}
