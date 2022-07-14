<?php

namespace tests\RavenDB\Test\Client\_CrudTest\Entities;

class FamilyMembers
{
    private MemberArray $members;

    public function getMembers(): MemberArray
    {
        return $this->members;
    }

    public function setMembers(MemberArray $members): void
    {
        $this->members = $members;
    }
}
