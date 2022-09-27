<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14272Test;

class UserTalk
{
    private ?TalkUserDefArray $userDefs = null;
    private ?string $name;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUserDefs(): ?TalkUserDefArray
    {
        return $this->userDefs;
    }

    public function setUserDefs(?TalkUserDefArray $userDefs): void
    {
        $this->userDefs = $userDefs;
    }
}
