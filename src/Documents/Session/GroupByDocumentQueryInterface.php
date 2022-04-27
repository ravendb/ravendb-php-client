<?php

namespace RavenDB\Documents\Session;

// !status: DONE
interface GroupByDocumentQueryInterface
{
    function selectKey(?string $fieldName = null, ?string $projectedName = null): GroupByDocumentQueryInterface;

    function selectSum(?GroupByField $field, GroupByField ...$fields): DocumentQueryInterface;

    function selectCount(string $projectedName = 'count'): DocumentQueryInterface;
}
