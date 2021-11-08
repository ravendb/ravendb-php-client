<?php

namespace RavenDB\Http;

class ClusterTopology
{
    private string $lastNodeId;
    private string $topologyId;
    private int $etag;

    // sve sto je zakomentarisano treba implementirati i kad se implementira potrebno je ukloniti iz komentara

    private array $members = [];
    private array $promotables = [];
    private array $watchers = [];


//    public boolean contains(String node) {
//        if (members != null && members.containsKey(node)) {
//            return true;
//        }
//if (promotables != null && promotables.containsKey(node)) {
//    return true;
//}
//
//return watchers != null && watchers.containsKey(node);
//}
//
//public String getUrlFromTag(String tag) {
//    if (tag == null) {
//        return null;
//    }
//
//    if (members != null && members.containsKey(tag)) {
//        return members.get(tag);
//    }
//
//    if (promotables != null && promotables.containsKey(tag)) {
//        return promotables.get(tag);
//    }
//
//    if (watchers != null && watchers.containsKey(tag)) {
//        return watchers.get(tag);
//    }
//
//    return null;
//}
//
//    public Map<String, String> getAllNodes() {
//Map<String, String> result = new HashMap<>();
//        if (members != null) {
//            for (Map.Entry<String, String> entry : members.entrySet()) {
//                result.put(entry.getKey(), entry.getValue());
//            }
//        }
//
//        if (promotables != null) {
//            for (Map.Entry<String, String> entry : promotables.entrySet()) {
//                result.put(entry.getKey(), entry.getValue());
//            }
//        }
//
//        if (watchers != null) {
//            for (Map.Entry<String, String> entry : watchers.entrySet()) {
//                result.put(entry.getKey(), entry.getValue());
//            }
//        }
//
//        return result;
//    }
//

    public function getMembers(): array
    {
        return $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    public function getPromotables(): array
    {
        return $this->promotables;
    }

    public function setPromotables(array $promotables): void
    {
        $this->promotables = $promotables;
    }

    public function getWatchers(): array
    {
        return $this->watchers;
    }

    public function setWatchers(array $watchers): void
    {
        $this->watchers = $watchers;
    }

    public function getLastNodeId(): string
    {
        return $this->lastNodeId;
    }

    public function setLastNodeId(string $lastNodeId): void
    {
        $this->lastNodeId = $lastNodeId;
    }

    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    public function setTopologyId(string $topologyId): void
    {
        $this->topologyId = $topologyId;
    }

    public function getEtag(): int
    {
        return $this->etag;
    }

    public function setEtag(int $etag): void
    {
        $this->etag = $etag;
    }
}
