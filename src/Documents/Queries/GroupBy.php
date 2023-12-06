<?php

namespace RavenDB\Documents\Queries;

class GroupBy
{
    private string $field;
    private GroupByMethod $method;

    private function __construct()
    {
        // empty
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getMethod(): GroupByMethod
    {
        return $this->method;
    }

    public static function field(string $fieldName): GroupBy
    {
        $groupBy = new GroupBy();
        $groupBy->field = $fieldName;
        $groupBy->method = GroupByMethod::none();

        return $groupBy;
    }

    public static function array(string $fieldName): GroupBy
    {
        $groupBy = new GroupBy();
        $groupBy->field = $fieldName;
        $groupBy->method = GroupByMethod::array();
        return $groupBy;
    }
}
