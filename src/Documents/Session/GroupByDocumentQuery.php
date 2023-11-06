<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\IllegalArgumentException;

class GroupByDocumentQuery implements GroupByDocumentQueryInterface
{
    private DocumentQuery $query;

    public function __construct(DocumentQuery $query)
    {
        $this->query = $query;
    }

    public function selectKey(?string $fieldName = null, ?string $projectedName = null): GroupByDocumentQueryInterface
    {
        $this->query->_groupByKey($fieldName, $projectedName);
        return $this;
    }

    public function selectSum(?GroupByField $field, GroupByField ...$fields): DocumentQueryInterface
 {
        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }

        $this->query->_groupBySum($field->getFieldName(), $field->getProjectedName());

        if ($fields == null || count($fields) == 0) {
            return $this->query;
        }

        foreach ($fields as $f) {
            $this->query->_groupBySum($f->getFieldName(), $f->getProjectedName());
        }

        return $this->query;
    }

    public function selectCount(string $projectedName = 'count'): DocumentQueryInterface
    {
        $this->query->_groupByCount($projectedName);
        return $this->query;
    }
}
