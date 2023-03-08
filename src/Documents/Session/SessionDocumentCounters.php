<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Operations\Counters\CounterDetail;
use RavenDB\Documents\Operations\Counters\CountersDetail;
use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use RavenDB\Type\StringList;

class SessionDocumentCounters extends SessionCountersBase implements SessionDocumentCountersInterface
{
    public function __construct(?InMemoryDocumentSessionOperations $session, string|object $idOrEntity) {
        parent::__construct($session, $idOrEntity);
    }


//    public Map<String, Long> getAll() {
//        Tuple<Boolean, Map<String, Long>> cache = session.getCountersByDocId().get(docId);
//
//        if (cache == null) {
//            cache = Tuple.create(false, new TreeMap<>(String::compareToIgnoreCase));
//        }
//
//        boolean missingCounters = !cache.first;
//
//        DocumentInfo document = session.documentsById.getValue(docId);
//        if (document != null) {
//            JsonNode metadataCounters = document.getMetadata().get(Constants.Documents.Metadata.COUNTERS);
//            if (metadataCounters == null || metadataCounters.isNull()) {
//                missingCounters = false;
//            } else if (cache.second.size() >= metadataCounters.size()) {
//                missingCounters = false;
//
//                for (JsonNode c : metadataCounters) {
//                    if (cache.second.containsKey(c.textValue())) {
//                        continue;
//                    }
//                    missingCounters = true;
//                    break;
//                }
//            }
//        }
//
//        if (missingCounters) {
//            // we either don't have the document in session and GotAll = false,
//            // or we do and cache doesn't contain all metadata counters
//
//            session.incrementRequestCount();
//
//            CountersDetail details = session.getOperations().send(new GetCountersOperation(docId), session.sessionInfo);
//            cache.second.clear();
//
//            for (CounterDetail counterDetail : details.getCounters()) {
//                cache.second.put(counterDetail.getCounterName(), counterDetail.getTotalValue());
//            }
//        }
//
//        cache.first = true;
//
//        if (!session.noTracking) {
//            session.getCountersByDocId().put(docId, cache);
//        }
//
//        return cache.second;
//    }

    public function get(string|StringList|array $counters): int|array
    {
        if (is_string($counters)) {
            return $this->getSingle($counters);
        }

        return $this->getArray($counters);
    }

    private function getSingle(string $counter): int
    {
        $value = null;

        if (array_key_exists($this->docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$this->docId];
            if (array_key_exists($counter, $cache[1])) {
                return $cache[1][$counter];
            }
        } else {
            $cache = [false, []];
        }

        $document = $this->session->documentsById->getValue($this->docId);
        $metadataHasCounterName = false;
        if ($document != null) {
            if (array_key_exists(DocumentsMetadata::COUNTERS, $document->getMetadata())) {
                $metadataCounters = $document->getMetadata()[DocumentsMetadata::COUNTERS];
                if ($metadataCounters != null) {
                    foreach ($metadataCounters as $node) {
                        if (strcasecmp(strval($node), $counter) == 0) {
                            $metadataHasCounterName = true;
                        }
                    }
                }
            }
        }
        if ((($document == null) && !$cache[0]) || $metadataHasCounterName) {
            // we either don't have the document in session and GotAll = false,
            // or we do, and it's metadata contains the counter name

            $this->session->incrementRequestCount();

            /** @var CountersDetail $details */
            $details = $this->session->getOperations()->send(new GetCountersOperation($this->docId, $counter), $this->session->getSessionInfo());
            if (!empty($details->getCounters())) {

                /** @var ?CounterDetail $counterDetail */
                $counterDetail = $details->getCounters()[0];
                $value = $counterDetail?->getTotalValue();
            }
        }

        $cache[1][$counter] = $value;

        if (!$this->session->noTracking) {
            $this->session->getCountersByDocId()[$this->docId] = $cache;
        }

        return $value;
    }

    private function getArray(StringList|array $counters): array
    {
        if (is_array($counters)) {
            $counters = StringList::fromArray($counters);
        }


        if (array_key_exists($this->docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$this->docId];
        } else {
            $cache = [false, []] ; //Tuple.create(false, new TreeMap<>(String::compareToIgnoreCase));
        }

        $metadataCounters = null;
        $document = $this->session->documentsById->getValue($this->docId);
        if ($document != null) {
            if (array_key_exists(DocumentsMetadata::COUNTERS, $document->getMetadata())) {
                $metadataCounters = $document->getMetadata()[DocumentsMetadata::COUNTERS];
            }
        }

        $result = [];

        foreach ($counters as $counter) {
            if (array_key_exists($counter, $cache[1])) {
                $hasCounter = true;
                $val = $cache[1][$counter];
            } else {
                $hasCounter = false;
                $val = null;
            }
            $notInMetadata = true;

            if ($document != null && $metadataCounters != null) {
                foreach ($metadataCounters as $metadataCounter) {
                    if (strcasecmp(strval($metadataCounter), $counter) == 0) {
                        $notInMetadata = false;
                    }
                }
            }
            if ($hasCounter || $cache[0] || ($document != null && $notInMetadata)) {
                // we either have value in cache,
                // or we have the metadata and the counter is not there,
                // or GotAll

                $result[$counter] = $val;
                continue;
            }

            $result = [];

            $this->session->incrementRequestCount();

            /** @var CountersDetail $details */
            $details = $this->session->getOperations()->send(new GetCountersOperation($this->docId, $counters), $this->session->getSessionInfo());

            foreach ($details->getCounters() as $counterDetail) {
                if ($counterDetail == null) {
                    continue;
                }
                $cache[1][$counterDetail->getCounterName()] = $counterDetail->getTotalValue();
                $result[$counterDetail->getCounterName()] = $counterDetail->getTotalValue();
            }

            break;
        }

        if (!$this->session->noTracking) {
            $this->session->getCountersByDocId()[$this->docId] = $cache;
        }

        return $result;
    }
}
