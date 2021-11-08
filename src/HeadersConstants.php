<?php

namespace RavenDB;

class HeadersConstants
{
    const REQUEST_TIME = "Raven-Request-Time";

    const REFRESH_TOPOLOGY = "Refresh-Topology";

    const TOPOLOGY_ETAG = "Topology-Etag";

    const LAST_KNOWN_CLUSTER_TRANSACTION_INDEX = "Known-Raft-Index";

    const CLIENT_CONFIGURATION_ETAG = "Client-Configuration-Etag";

    const REFRESH_CLIENT_CONFIGURATION = "Refresh-Client-Configuration";

    const CLIENT_VERSION = "Raven-Client-Version";
    const SERVER_VERSION = "Raven-Server-Version";

    const ETAG = "ETag";

    const IF_NONE_MATCH = "If-None-Match";
    const TRANSFER_ENCODING = "Transfer-Encoding";
    const CONTENT_ENCODING = "Content-Encoding";
    const CONTENT_LENGTH = "Content-Length";
}
