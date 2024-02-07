<?php

namespace RavenDB\Documents\Operations\Etl\Olap;

use RavenDB\Documents\Operations\Etl\EtlType;

class OlapEtlConfiguration
{
    private ?string $runFrequency = null;
    private ?OlapEtlFileFormat $format = null;
    private ?string $customPartitionValue = null;
    private ?OlapEtlTableList $olapTables = null;

    public function getEtlType(): EtlType
    {
        return EtlType::olap();
    }

    public function getRunFrequency(): ?string
    {
        return $this->runFrequency;
    }

    public function setRunFrequency(?string $runFrequency): void
    {
        $this->runFrequency = $runFrequency;
    }

    public function getFormat(): ?OlapEtlFileFormat
    {
        return $this->format;
    }

    public function setFormat(?OlapEtlFileFormat $format): void
    {
        $this->format = $format;
    }

    public function getCustomPartitionValue(): ?string
    {
        return $this->customPartitionValue;
    }

    public function setCustomPartitionValue(?string $customPartitionValue): void
    {
        $this->customPartitionValue = $customPartitionValue;
    }

    public function getOlapTables(): ?OlapEtlTableList
    {
        return $this->olapTables;
    }

    public function setOlapTables(?OlapEtlTableList $olapTables): void
    {
        $this->olapTables = $olapTables;
    }
}
