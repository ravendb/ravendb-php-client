<?php

namespace RavenDB\Documents\Session;


abstract class MethodCall
{
    public array $args = [];
    public ?string $accessPath = null;
}
