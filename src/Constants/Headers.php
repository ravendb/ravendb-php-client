<?php

namespace RavenDB\Constants;

class Headers
{
    public const REQUEST_TIME = "Raven-Request-Time";
    public const REFRESH_TOPOLOGY = "Refresh-Topology";
    public const TOPOLOGY_ETAG = "Topology-Etag";
    public const LAST_KNOWN_CLUSTER_TRANSACTION_INDEX = "Known-Raft-Index";
    public const CLIENT_CONFIGURATION_ETAG = "Client-Configuration-Etag";
    public const REFRESH_CLIENT_CONFIGURATION = "Refresh-Client-Configuration";
    public const CLIENT_VERSION = "Raven-Client-Version";
    public const SERVER_VERSION = "Raven-Server-Version";
    public const ETAG = "ETag";
    public const IF_NONE_MATCH = "If-None-Match";
    public const TRANSFER_ENCODING = "Transfer-Encoding";
    public const CONTENT_ENCODING = "Content-Encoding";
    public const CONTENT_LENGTH = "Content-Length";
    public const DATABASE_MISSING = "Database-Missing";
}
