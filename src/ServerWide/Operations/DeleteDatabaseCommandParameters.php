<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Type\StringArray;

// !status: DONE
class DeleteDatabaseCommandParameters
{
        private StringArray $databaseNames;
        private bool $hardDelete;
        private StringArray $fromNodes;
        private \DateInterval $timeToWaitForConfirmation;

        public function getDatabaseNames(): StringArray {
            return $this->databaseNames;
        }

        public function setDatabaseNames(StringArray $databaseNames): void
        {
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

        public function getTimeToWaitForConfirmation(): \DateInterval {
            return $this->timeToWaitForConfirmation;
        }

        public function setTimeToWaitForConfirmation(\DateInterval $timeToWaitForConfirmation): void {
            $this->timeToWaitForConfirmation = $timeToWaitForConfirmation;
        }
}
