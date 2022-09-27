<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14272Test;

class TalkUserIds
{
        private ?TalkUserDefArray $userDefs = null;

    public function getUserDefs(): ?TalkUserDefArray
    {
        return $this->userDefs;
    }

    public function setUserDefs(?TalkUserDefArray $userDefs): void
    {
        $this->userDefs = $userDefs;
    }
}
