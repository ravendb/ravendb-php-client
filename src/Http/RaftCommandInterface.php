<?php

namespace RavenDB\Http;


interface RaftCommandInterface
{
    public function getRaftUniqueRequestId(): string;
}
