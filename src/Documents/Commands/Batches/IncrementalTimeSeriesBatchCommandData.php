<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Operations\TimeSeries\IncrementOperationList;

class IncrementalTimeSeriesBatchCommandData extends TimeSeriesCommandData
{
    public function __construct(?string $documentId, ?string $name, IncrementOperationList|array|null $increments = null)
    {
        parent::__construct($documentId, $name);

        if ($increments != null) {
            if (is_array($increments)) {
                $increments = IncrementOperationList::fromArray($increments);
            }

            /** TimeSeriesOperation.IncrementOperation */
            foreach ($increments as $incrementOperation) {
                $this->getTimeSeries()->increment($incrementOperation);
            }
        }
    }

    public function getType(): ?CommandType
    {
        return CommandType::timeSeriesWithIncrements();
    }
}
