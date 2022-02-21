<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Session\ForceRevisionStrategy;

class PutCommandDataWithJson extends PutCommandDataBase
{
    /**
     * @throws \RavenDB\Exceptions\IllegalArgumentException
     */
    public function __construct(
        string $id,
        ?string $changeVector,
        ?string $originalChangeVector,
        array $document,
        ?ForceRevisionStrategy $strategy = null
    ) {
        parent::__construct($id, $changeVector, $originalChangeVector, $document, $strategy);
    }
}
