<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Session\OrderingType;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;

class OrderByToken extends QueryToken
{
    private ?string $fieldName = null;
    private bool $descending = false;
    private ?string $sorterName = null;
    private ?OrderingType $ordering = null;

    /**
     * @param string|null $fieldName
     * @param bool $descending
     * @param string|OrderingType $sorterNameOrOrdering
     */
    private function __construct(?string $fieldName, bool $descending, $sorterNameOrOrdering)
    {
        $this->fieldName = $fieldName;
        $this->descending = $descending;
        if (is_string($sorterNameOrOrdering)) {
            $this->sorterName = $sorterNameOrOrdering;
            $this->ordering = null;
        } else {
            $this->sorterName = null;
            $this->ordering = $sorterNameOrOrdering;
        }

    }

    private static ?OrderByToken $RANDOM = null;
    public static function random(): OrderByToken
    {
        if (self::$RANDOM == null) {
            self::$RANDOM = new OrderByToken("random()", false, OrderingType::string());
        }
        return self::$RANDOM;
    }

    public static ?OrderByToken $SCORE_ASCENDING = null;
    public static function scoreAscending(): OrderByToken
    {
        if (self::$SCORE_ASCENDING == null) {
            self::$SCORE_ASCENDING = new OrderByToken("score()", false, OrderingType::string());
        }

        return self::$SCORE_ASCENDING;
    }

    public static ?OrderByToken $SCORE_DESCENDING = null;
    public static function scoreDescending(): OrderByToken
    {
        if (self::$SCORE_DESCENDING == null) {
            self::$SCORE_DESCENDING = new OrderByToken("score()", true, OrderingType::string());
        }
        return self::$SCORE_DESCENDING;
    }

    public static function createDistanceAscendingFromPoint(string $fieldName, string $latitudeParameterName, string $longitudeParameterName, ?string $roundFactorParameterName = null): OrderByToken
    {
        $fieldName = "spatial.distance(" . $fieldName . ", spatial.point($" . $latitudeParameterName . ", $" . $longitudeParameterName . ")" . ($roundFactorParameterName == null ? "" : ", $" . $roundFactorParameterName) . ")";
        return new OrderByToken($fieldName, false, OrderingType::string());
    }

    public static function createDistanceAscendingFromWkt(string $fieldName, string $shapeWktParameterName, ?string $roundFactorParameterName = null): OrderByToken
    {
        $fieldName = "spatial.distance(" . $fieldName . ", spatial.wkt($" . $shapeWktParameterName . ")" . ($roundFactorParameterName == null ? "" : ", $" . $roundFactorParameterName) . ")";
        return new OrderByToken($fieldName, false, OrderingType::string());
    }

    public static function createDistanceDescendingFromPoint(string $fieldName, string $latitudeParameterName, string $longitudeParameterName, ?string $roundFactorParameterName = null): OrderByToken
    {
        $fieldName = "spatial.distance(" . $fieldName . ", spatial.point($" . $latitudeParameterName . ", $" . $longitudeParameterName . ")" . ($roundFactorParameterName == null ? "" : ", $" . $roundFactorParameterName) . ")";
        return new OrderByToken($fieldName, true, OrderingType::string());
    }

    public static function createDistanceDescendingFromWkt(string $fieldName, string $shapeWktParameterName, ?string $roundFactorParameterName = null): OrderByToken
    {
        $fieldName = "spatial.distance(" . $fieldName . ", spatial.wkt($" . $shapeWktParameterName . ")" . ($roundFactorParameterName == null ? "" : ", $" . $roundFactorParameterName) . ")";
        return new OrderByToken($fieldName, true, OrderingType::string());
    }

    public static function createRandom(?string $seed = null): OrderByToken
    {
        if ($seed == null) {
            throw new IllegalArgumentException("seed cannot be null");
        }

        return new OrderByToken("random('" . str_replace("'", "''", $seed) . "')", false, OrderingType::string());
    }

    /**
     * @param string $fieldName
     * @param string|OrderingType $sorterNameOrOrdering
     *
     * @return OrderByToken
     */
    public static function createAscending(string $fieldName, $sorterNameOrOrdering): OrderByToken
    {
        return new OrderByToken($fieldName, false, $sorterNameOrOrdering);
    }

    /**
     * @param string|null $fieldName
     * @param string|OrderingType $sorterNameOrOrdering
     *
     * @return OrderByToken
     */
    public static function createDescending(?string $fieldName, $sorterNameOrOrdering): OrderByToken
    {
        return new OrderByToken($fieldName, true, $sorterNameOrOrdering);
    }

    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->sorterName != null) {
            $writer
                ->append("custom(");
        }

        $this->writeField($writer, $this->fieldName);

        if ($this->sorterName != null) {
            $writer
                ->append(", '")
                ->append($this->sorterName)
                ->append("')");
        } else {
            switch ($this->ordering) {
                case OrderingType::LONG:
                    $writer->append(" as long");
                    break;
                case OrderingType::DOUBLE:
                    $writer->append(" as double");
                    break;
                case OrderingType::ALPHA_NUMERIC:
                    $writer->append(" as alphaNumeric");
                    break;
            }
        }

        if ($this->descending) { // we only add this if we have to, ASC is the default and reads nicer
            $writer->append(" desc");
        }
    }
}
