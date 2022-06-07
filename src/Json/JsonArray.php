<?php

namespace RavenDB\Json;

class JsonArray
{
    /**
     * We test does the given array should be converted to json array or json object
     * if all keys are int then it should be array, otherwise it will be object
     *
     * @param mixed $arr
     * @return bool
     */
    public static function isArray($arr): bool
    {
        if (!is_array($arr)) {
            return false;
        }

        foreach ($arr as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }
        return true;
    }
}
