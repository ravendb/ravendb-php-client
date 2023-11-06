<?php

namespace RavenDB\Utils;

use Ramsey\Uuid\Uuid;

class RaftIdGenerator
{
    private function __constructor() {
        // empty by design
    }

    public static function newId(): string
    {
        return Uuid::uuid4();
    }

    // if the don't care id is used it may cause that on retry/resend of the command we will end up in double applying of the command (once for the original request and for the retry).
    static public function dontCareId(): string {
        return "";
    }
}
