<?php

namespace RavenDB\Http;

use RavenDB\Type\Url;

class ServerNode
{
    private Url $url;
    private string $database;
    private string $clusterTag;
//    private Role serverRole;

//    public function getUrl(): string
//    {
//        return 'http://live-test.ravendb.net';
//    }

    public function getUrl(): Url
    {
        return $this->url;
    }

    public function setUrl(Url $url): void
    {
        $this->url = $url;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

    public function getClusterTag(): string
    {
        return $this->clusterTag;
    }

    public function setClusterTag(string $clusterTag): void
    {
        $this->clusterTag = $clusterTag;
    }
}
