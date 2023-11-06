<?php

namespace tests\RavenDB;

class ReplicationTestBase2
{
    private ?RemoteTestBase $parent = null;

    public function __construct(?RemoteTestBase $parent)
    {
        $this->parent = $parent;
    }

//    public void waitForConflict(IDocumentStore store, String id) throws InterruptedException {
//        Stopwatch sw = Stopwatch.createStarted();
//        while (sw.elapsed(TimeUnit.MILLISECONDS) < 10_000) {
//            try (IDocumentSession session = store.openSession()) {
//                session.load(Object.class, id);
//
//                Thread.sleep(10);
//            } catch (ConflictException e) {
//                return;
//            }
//        }
//
//        throw new IllegalStateException("Waited for conflict on '" + id + "' but it did not happen");
//    }
}
