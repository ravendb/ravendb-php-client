<?php

namespace RavenDB\Documents\Identity;

interface HiLoIdGeneratorInterface
{
   public function generateNextIdFor(?string $database, null|string|object $collectionNameOrEntity): int;
}
