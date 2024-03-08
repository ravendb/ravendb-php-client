<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Session\AbstractDocumentQuery;
use RavenDB\Documents\Session\AbstractDocumentQueryInterface;
use RavenDB\Documents\Session\WhereParams;

class FilterFactory implements FilterFactoryInterface
{
    private ?AbstractDocumentQueryInterface $documentQuery = null;

    public function __construct(AbstractDocumentQueryInterface $documentQuery, ?int $filterLimit = null)
    {
        if ($filterLimit === null) {
            $filterLimit = PhpClient::INT_MAX_VALUE;
        }

        $this->documentQuery = $documentQuery;
        $this->setFilterLimit($filterLimit);
    }

    public function equals(string $fieldName, $value): FilterFactoryInterface
    {
        $this->documentQuery->_whereEquals($fieldName, $value);
        return $this;
    }


    public function equalsWithParams(WhereParams $whereParams): FilterFactoryInterface
    {
        $this->documentQuery->_whereEqualsWithParams($whereParams);
        return $this;
    }


    public function notEquals(?string $fieldName, $value): FilterFactoryInterface
    {
        $this->documentQuery->_whereNotEquals($fieldName, $value);
        return $this;
    }

    public function notEqualsWithParams(WhereParams $whereParams): FilterFactoryInterface
    {
        $this->documentQuery->_whereNotEqualsWithParams($whereParams);
        return $this;
    }

    public function greaterThan(?string $fieldName, $value): FilterFactoryInterface {
        $this->documentQuery->_whereGreaterThan($fieldName, $value);
        return $this;
    }

    public function greaterThanOrEqual(?string $fieldName, $value): FilterFactoryInterface {
        $this->documentQuery->_whereGreaterThanOrEqual($fieldName, $value);
        return $this;
    }

    public function lessThan(?string $fieldName, $value): FilterFactoryInterface {
        $this->documentQuery->_whereLessThan($fieldName, $value);
        return $this;
    }

    public function lessThanOrEqual(?string $fieldName, $value): FilterFactoryInterface {
        $this->documentQuery->_whereLessThanOrEqual($fieldName, $value);
        return $this;
    }

    public function andAlso(): FilterFactoryInterface {
        $this->documentQuery->_andAlso();
        return $this;
    }

    public function orElse(): FilterFactoryInterface {
        $this->documentQuery->_orElse();
        return $this;
    }

    public function not(): FilterFactoryInterface {
        $this->documentQuery->_negateNext();
        return $this;
    }

    public function openSubclause(): FilterFactoryInterface {
        $this->documentQuery->_openSubclause();
        return $this;
    }

    public function closeSubclause(): FilterFactoryInterface {
        $this->documentQuery->_closeSubclause();
        return $this;
    }

    private function setFilterLimit(int $limit): void
    {
        /** @var AbstractDocumentQuery $dc */
        $dc = $this->documentQuery;
        $dc->_addFilterLimit($limit);
    }
}
