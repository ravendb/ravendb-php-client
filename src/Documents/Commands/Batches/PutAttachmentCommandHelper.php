<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Exceptions\IllegalStateException;

class PutAttachmentCommandHelper
{
    static public function throwStreamWasAlreadyUsed(): void
    {
        throw new IllegalStateException("It is forbidden to re-use the same InputStream for more than one attachment. Use a unique InputStream per put attachment command.");
    }
}
