<?php

namespace RavenDB\Documents;

use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Type\UrlArray;
use RavenDB\Http\RequestExecutor;
use RavenDB\Documents\Conventions\DocumentConventions;

interface DocumentStoreInterface
{
    public function initialize(): DocumentStoreInterface;

    public function getRequestExecutor(string $databaseName = null): RequestExecutor;

    public function getUrls(): UrlArray;
    public function getDatabase(): string;

    public function getConventions(): DocumentConventions;

    /** Opens the session */
    public function openSession(string $database = ''): DocumentSessionInterface;
    public function openSessionWithOptions(SessionOptions $sessionOptions): DocumentSessionInterface;
}
