<?php

namespace RavenDB\Constants;

class DocumentsPeriodicBackup
{
    public const FULL_BACKUP_EXTENSION = "ravendb-full-backup";
    public const SNAPSHOT_EXTENSION = "ravendb-snapshot";
    public const ENCRYPTED_FULL_BACKUP_EXTENSION = ".ravendb-encrypted-full-backup";
    public const ENCRYPTED_SNAPSHOT_EXTENSION = ".ravendb-encrypted-snapshot";
    public const INCREMENTAL_BACKUP_EXTENSION = "ravendb-incremental-backup";
    public const ENCRYPTED_INCREMENTAL_BACKUP_EXTENSION = ".ravendb-encrypted-incremental-backup";
}
