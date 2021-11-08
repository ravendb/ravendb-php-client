<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;

interface DocumentSessionImplementationInterface extends DocumentSessionInterface, EagerSessionOperationsInterface
{

}
