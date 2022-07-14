<?php

namespace tests\RavenDB\Test\Issues\RavenDB_15825Test;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;

class ContactsIndex extends AbstractIndexCreationTask
{
        public function __construct()
        {
            parent::__construct();
            $this->map = "from contact in docs.contacts select new { companyId = contact.companyId, tags = contact.tags, active = contact.active }";
        }
}
