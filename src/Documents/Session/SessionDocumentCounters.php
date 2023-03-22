<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Operations\Counters\CounterDetail;
use RavenDB\Documents\Operations\Counters\CountersDetail;
use RavenDB\Documents\Operations\Counters\GetCountersOperation;
use RavenDB\Type\ExtendedArrayObject;
use RavenDB\Type\StringList;

class SessionDocumentCounters extends SessionCountersBase implements SessionDocumentCountersInterface
{
    public function __construct(?InMemoryDocumentSessionOperations $session, string|object $idOrEntity) {
        parent::__construct($session, $idOrEntity);
    }


    public function getAll(): array
    {
        if (array_key_exists($this->docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$this->docId];
        } else {
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [false, $array];
        }

        $missingCounters = !$cache[0];

        $document = $this->session->documentsById->getValue($this->docId);
        if ($document != null) {
            $metadataCounters = array_key_exists(DocumentsMetadata::COUNTERS, $document->getMetadata()) ? $document->getMetadata()[DocumentsMetadata::COUNTERS] : null;
            if (empty($metadataCounters)) {
                $missingCounters = false;
            } else if (count($cache[1]) >= count($metadataCounters)) {
                $missingCounters = false;

                foreach ($metadataCounters as $c) {
                    if ($cache[1]->offsetExists(strval($c))) {
                        continue;
                    }
                    $missingCounters = true;
                    break;
                }
            }
        }

        if ($missingCounters) {
            // we either don't have the document in session and GotAll = false,
            // or we do and cache doesn't contain all metadata counters

            $this->session->incrementRequestCount();

            $details = $this->session->getOperations()->send(new GetCountersOperation($this->docId), $this->session->getSessionInfo());
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache[1] = $array;

            /** @var CounterDetail $counterDetail */
            foreach ($details->getCounters() as $counterDetail) {
                $cache[1][$counterDetail->getCounterName()] = $counterDetail->getTotalValue();
            }
        }

        $cache[0] = true;

        if (!$this->session->noTracking) {
            $this->session->getCountersByDocId()[$this->docId] = $cache;
        }

        return $cache[1]->getArrayCopy();
    }

    public function get(string|StringList|array $counters): null|int|array
    {
        if (is_string($counters)) {
            return $this->getSingle($counters);
        }

        return $this->getArray($counters);
    }

    private function getSingle(string $counter): null|int
    {
        $value = null;

        if (array_key_exists($this->docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$this->docId];
            if ($cache[1]->offsetExists($counter)) {
                return $cache[1][$counter];
            }
        } else {
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [false, $array];
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
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [false, $array];
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
            if ($cache[1]->offsetExists($counter)) {
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
