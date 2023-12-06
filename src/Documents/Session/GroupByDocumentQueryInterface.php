<?php

namespace RavenDB\Documents\Session;


interface GroupByDocumentQueryInterface
{
    function selectKey(?string $fieldName = null, ?string $projectedName = null): GroupByDocumentQueryInterface;

    function selectSum(?GroupByField $field, GroupByField ...$fields): DocumentQueryInterface;

    function selectCount(string $projectedName = 'count'): DocumentQueryInterface;
}
