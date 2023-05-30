<?php

namespace tests\RavenDB;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Utils\Stopwatch;

class ReplicationTestBase extends RemoteTestBase
{
//    protected void modifyReplicationDestination(ReplicationNode replicationNode) {
//        // empty by design
//    }
//
//    public static class Marker {
//        private String id;
//
//        public String getId() {
//            return id;
//        }
//
//        public void setId(String id) {
//            this.id = id;
//        }
//    }
//
//    protected void ensureReplicating(IDocumentStore src, IDocumentStore dst) {
//        String id = "marker/" + UUID.randomUUID();
//
//        try (IDocumentSession s = src.openSession()) {
//            s.store(new Marker(), id);
//            s.saveChanges();
//        }
//
//        assertThat(waitForDocumentToReplicate(dst, ObjectNode.class, id, 15_000))
//            .isNotNull();
//    }
//
//    protected List<ModifyOngoingTaskResult> setupReplication(IDocumentStore fromStore, IDocumentStore... destinations) {
//        List<ModifyOngoingTaskResult> result = new ArrayList<>();
//
//        for (IDocumentStore store : destinations) {
//            ExternalReplication databaseWatcher = new ExternalReplication(store.getDatabase(), "ConnectionString-" + store.getIdentifier());
//            modifyReplicationDestination(databaseWatcher);
//
//            result.add(addWatcherToReplicationTopology(fromStore, databaseWatcher));
//        }
//
//        return result;
//    }
//
//    protected ModifyOngoingTaskResult addWatcherToReplicationTopology(IDocumentStore store, ExternalReplicationBase watcher, String... urls) {
//
//        RavenConnectionString connectionString = new RavenConnectionString();
//        connectionString.setName(watcher.getConnectionStringName());
//        connectionString.setDatabase(watcher.getDatabase());
//
//        String[] urlsToUse = urls != null && urls.length > 0 ? urls : store.getUrls();
//
//        connectionString.setTopologyDiscoveryUrls(urlsToUse);
//
//        store.maintenance().send(new PutConnectionStringOperation<>(connectionString));
//
//        IMaintenanceOperation<ModifyOngoingTaskResult> op;
//
//        if (watcher instanceof PullReplicationAsSink) {
//            op = new UpdatePullReplicationAsSinkOperation((PullReplicationAsSink) watcher);
//        } else if (watcher instanceof ExternalReplication) {
//            op = new UpdateExternalReplicationOperation((ExternalReplication) watcher);
//        } else {
//            throw new IllegalArgumentException("Unrecognized type: " + watcher.getClass());
//        }
//
//        return store.maintenance().send(op);
//    }
//
//    protected static ModifyOngoingTaskResult deleteOngoingTask(DocumentStore store, long taskId, OngoingTaskType taskType) {
//        DeleteOngoingTaskOperation op = new DeleteOngoingTaskOperation(taskId, taskType);
//        return store.maintenance().send(op);
//    }

    protected function waitForDocumentToReplicate(DocumentStoreInterface $store, ?string $className, ?string $id, int $timeout): mixed
    {
        $sw = Stopwatch::createStarted();

        while ($sw->elapsedInMillis() <= $timeout) {
            $session = $store->openSession();
            try {
                $doc = $session->load($className, $id);
                if ($doc != null) {
                    return $doc;
                }
            } finally {
                $session->close();
            }

            usleep(500000);
        }

        return null;
    }

//    protected static int getPromotableCount(IDocumentStore store, String databaseName) {
//        DatabaseRecordWithEtag res = store.maintenance().server().send(new GetDatabaseRecordOperation(databaseName));
//        if (res == null) {
//            return -1;
//        }
//        return res.getTopology().getPromotables().size();
//    }
//
//    protected static int getRehabCount(IDocumentStore store, String databaseName) {
//        DatabaseRecordWithEtag res = store.maintenance().server().send(new GetDatabaseRecordOperation(databaseName));
//        if (res == null) {
//            return -1;
//        }
//        return res.getTopology().getRehabs().size();
//    }
//
//    protected static int getMembersCount(IDocumentStore store, String databaseName) {
//        DatabaseRecordWithEtag res = store.maintenance().server().send(new GetDatabaseRecordOperation(databaseName));
//        if (res == null) {
//            return -1;
//        }
//        return res.getTopology().getMembers().size();
//    }
//
//    protected static int getDeletionCount(IDocumentStore store, String databaseName) {
//        DatabaseRecordWithEtag res = store.maintenance().server().send(new GetDatabaseRecordOperation(databaseName));
//        if (res == null) {
//            return -1;
//        }
//        return res.getDeletionInProgress().size();
//    }
//
//    protected String getClientCertificateAsBase64() throws Exception {
//        KeyStore keyStore = getTestClientCertificate();
//
//        String alias = keyStore.aliases().nextElement();
//        Certificate certificate = keyStore.getCertificate(alias);
//        return org.apache.commons.codec.binary.Base64.encodeBase64String(certificate.getEncoded());
//    }
}
