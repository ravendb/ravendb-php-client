<?php

namespace RavenDB\Constants;

class DocumentsMetadata
{
    public const COLLECTION = "@collection";
    public const PROJECTION = "@projection";
    public const KEY = "@metadata";
    public const ID = "@id";
    public const CONFLICT = "@conflict";
    public const ID_PROPERTY = "Id";
    public const FLAGS = "@flags";
    public const ATTACHMENTS = "@attachments";
    public const COUNTERS = "@counters";
    public const TIME_SERIES = "@timeseries";
    public const REVISION_COUNTERS = "@counters-snapshot";
    public const REVISION_TIME_SERIES = "@timeseries-snapshot";
    public const INDEX_SCORE = "@index-score";
    public const LAST_MODIFIED = "@last-modified";
    public const RAVEN_JAVA_TYPE = "Raven-Java-Type";
    public const RAVEN_PHP_TYPE = "Raven-PHP-Type";
    public const CHANGE_VECTOR = "@change-vector";
    public const EXPIRES = "@expires";
    public const ALL_DOCUMENTS_COLLECTION = "@all_docs";
}
