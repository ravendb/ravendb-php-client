<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class DeleteDatabaseCommandParameters
{
        /** @SerializedName("DatabaseNames") */
        private StringArray $databaseNames;

        /** @SerializedName("HardDelete") */
        private bool $hardDelete = false;

        /** @SerializedName("FromNodes") */
        private StringArray $fromNodes;

        /** @SerializedName("TimeToWaitForConfirmation") */
        private ?\DateInterval $timeToWaitForConfirmation = null;

        public function __construct()
        {
            $this->databaseNames = new StringArray();
            $this->fromNodes = new StringArray();
        }

        public function getDatabaseNames(): StringArray {
            return $this->databaseNames;
        }

        public function setDatabaseNames(StringArray|array $databaseNames): void
        {
            if (is_array($databaseNames)) {
                $databaseNames = StringArray::fromArray($databaseNames);
            }
            $this->databaseNames = $databaseNames;
        }

        public function isHardDelete(): bool
        {
            return $this->hardDelete;
        }

        public function setHardDelete(bool $hardDelete): void
        {
            $this->hardDelete = $hardDelete;
        }

        public function getFromNodes(): StringArray
        {
            return $this->fromNodes;
        }

        public function setFromNodes(StringArray $fromNodes): void
        {
            $this->fromNodes = $fromNodes;
        }

        public function getTimeToWaitForConfirmation(): ?\DateInterval
        {
            return $this->timeToWaitForConfirmation;
        }

        public function setTimeToWaitForConfirmation(?\DateInterval $timeToWaitForConfirmation): void {
            $this->timeToWaitForConfirmation = $timeToWaitForConfirmation;
        }
}
