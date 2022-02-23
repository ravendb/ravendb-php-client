<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;

// !status: DONE
class CertificateRawData implements ResultInterface
{
    private $rawData;

    public function getRawData()
    {
        return $this->rawData;
    }

    public function setRawData($rawData): void
    {
        $this->rawData = $rawData;
    }
}
