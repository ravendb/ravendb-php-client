<?php

namespace RavenDB\Utils;

class RaftIdGenerator
{
    private function __constructor() {
        // empty by design
    }

    // @todo: implement this UUID generator through Ramsey UUID...
    public static function newId():strung {
        return "123456789"; // UUID.randomUUID().toString();
    }

    // if the don't care id is used it may cause that on retry/resend of the command we will end up in double applying of the command (once for the original request and for the retry).
    static public function dontCareId(): string {
        return "";
    }
}
