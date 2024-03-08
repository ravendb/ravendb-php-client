<?php

namespace RavenDB\Documents\Commands\Batches;

use RavenDB\Documents\Operations\TimeSeries\AppendOperationList;
use RavenDB\Documents\Operations\TimeSeries\DeleteOperationList;

class TimeSeriesBatchCommandData extends TimeSeriesCommandData
{
    public function __construct(?string $documentId, ?string $name,
                                      AppendOperationList|array|null $appends = null,
                                      DeleteOperationList|array|null $deletes = null)
    {
        parent::__construct($documentId, $name);

        if ($appends !== null) {
            if (is_array($appends)) {
                $appends = AppendOperationList::fromArray($appends);
            }

            foreach ($appends as $appendOperation) {
                $this->getTimeSeries()->append($appendOperation);
            }
        }

        if ($deletes !== null) {
            if (is_array($deletes)) {
                $deletes = DeleteOperationList::fromArray($deletes);
            }

            foreach ($deletes as $deleteOperation) {
                $this->getTimeSeries()->delete($deleteOperation);
            }
        }
    }

    public function getType(): ?CommandType
    {
        return CommandType::timeSeries();
    }
}
