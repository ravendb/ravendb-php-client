<?php

namespace RavenDB\Http;

// !status: DONE
interface RaftCommandInterface
{
    public function getRaftUniqueRequestId(): string;
}
