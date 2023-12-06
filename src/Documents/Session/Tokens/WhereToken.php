<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Constants\DocumentsIndexingSpatial;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringBuilder;

class WhereToken extends QueryToken
{
    private string $fieldName;
    private WhereOperator $whereOperator;
    private ?string $parameterName = null;
    private WhereOptions $options;

    protected function __construct()
    {
    }

    public static function create(
        WhereOperator $op, string $fieldName, ?string $parameterName, ?WhereOptions $options = null
    ): WhereToken {
        $token = new WhereToken();
        $token->fieldName = $fieldName;
        $token->parameterName = $parameterName;
        $token->whereOperator = $op;
        $token->options = $options ?? WhereOptions::defaultOptions();
        return $token;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getWhereOperator(): WhereOperator
    {
        return $this->whereOperator;
    }

    public function setWhereOperator(WhereOperator $whereOperator): void
    {
        $this->whereOperator = $whereOperator;
    }

    public function getParameterName(): ?string
    {
        return $this->parameterName;
    }

    public function setParameterName(?string $parameterName): void
    {
        $this->parameterName = $parameterName;
    }

    public function getOptions(): WhereOptions
    {
        return $this->options;
    }

    public function setOptions(WhereOptions $options): void
    {
        $this->options = $options;
    }

    public function addAlias(string $alias): WhereToken
    {
        if ("id()" == $this->fieldName) {
            return $this;
        }

        $whereToken = new WhereToken();
        $whereToken->setFieldName($alias . "." . $$this->fieldName);
        $whereToken->setParameterName($this->parameterName);
        $whereToken->setWhereOperator($this->whereOperator);
        $whereToken->setOptions($this->options);

        return $whereToken;
    }

    private function writeMethod(StringBuilder $writer): bool
    {
        if ($this->options->getMethod() != null) {
            switch ($this->options->getMethod()->methodType->getValue()) {
                case MethodsType::CMP_X_CHG:
                    $writer->append("cmpxchg(");
                    break;
                default:
                    throw new IllegalArgumentException("Unsupported method: " . $this->options->getMethod()->methodType);
            }

            $first = true;
            foreach ($this->options->getMethod()->parameters as $parameter) {
                if (!$first) {
                    $writer->append(",");
                }
                $first = false;
                $writer->append("$");
                $writer->append($parameter);
            }
            $writer->append(")");

            if ($this->options->getMethod()->property != null) {
                $writer
                    ->append(".")
                    ->append($this->options->getMethod()->property);
            }
            return true;
        }

        return false;
    }


    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->options->getBoost() !== null) {
            $writer->append("boost(");
        }

        if ($this->options->getFuzzy() != null) {
            $writer->append("fuzzy(");
        }

        if ($this->options->getProximity() != null) {
            $writer->append("proximity(");
        }

        if ($this->options->isExact()) {
            $writer->append("exact(");
        }

        switch ($this->whereOperator->getValue()) {
            case WhereOperator::SEARCH:
                $writer->append("search(");
                break;
            case WhereOperator::LUCENE:
                $writer->append("lucene(");
                break;
            case WhereOperator::STARTS_WITH:
                $writer->append("startsWith(");
                break;
            case WhereOperator::ENDS_WITH:
                $writer->append("endsWith(");
                break;
            case WhereOperator::EXISTS:
                $writer->append("exists(");
                break;
            case WhereOperator::SPATIAL_WITHIN:
                $writer->append("spatial.within(");
                break;
            case WhereOperator::SPATIAL_CONTAINS:
                $writer->append("spatial.contains(");
                break;
            case WhereOperator::SPATIAL_DISJOINT:
                $writer->append("spatial.disjoint(");
                break;
            case WhereOperator::SPATIAL_INTERSECTS:
                $writer->append("spatial.intersects(");
                break;
            case WhereOperator::REGEX:
                $writer->append("regex(");
                break;
        }

        $this->writeInnerWhere($writer);

        if ($this->options->isExact()) {
            $writer->append(")");
        }

        if ($this->options->getProximity() != null) {
            $writer
                    ->append(", ")
                    ->append($this->options->getProximity())
                    ->append(")");
        }

        if ($this->options->getFuzzy() != null) {
            $writer
                    ->append(", ")
                    ->append($this->options->getFuzzy())
                    ->append(")");
        }

        if ($this->options->getBoost() != null) {
            $writer
                    ->append(", ")
                    ->append($this->options->getBoost())
                    ->append(")");
        }
    }

    private function writeInnerWhere(StringBuilder $writer): void
    {

        $this->writeField($writer, $this->fieldName);

        switch ($this->whereOperator->getValue()) {
            case WhereOperator::EQUALS:
                $writer
                    ->append(" = ");
                break;

            case WhereOperator::NOT_EQUALS:
                $writer
                    ->append(" != ");
                break;
            case WhereOperator::GREATER_THAN:
                $writer
                    ->append(" > ");
                break;
            case WhereOperator::GREATER_THAN_OR_EQUAL:
                $writer
                    ->append(" >= ");
                break;
            case WhereOperator::LESS_THAN:
                $writer
                    ->append(" < ");
                break;
            case WhereOperator::LESS_THAN_OR_EQUAL:
                $writer
                    ->append(" <= ");
                break;
            default:
                $this->specialOperator($writer);
                return;
        }

        if (!$this->writeMethod($writer)) {
            $writer
                ->append("$")
                ->append($this->parameterName);
        }
    }

    private function specialOperator(StringBuilder $writer): void
    {
        switch ($this->whereOperator->getValue())
        {
            case WhereOperator::IN:
                $writer
                    ->append(" in ($")
                    ->append($this->parameterName)
                    ->append(")");
                break;
            case WhereOperator::ALL_IN:
                $writer
                    ->append(" all in ($")
                    ->append($this->parameterName)
                    ->append(")");
                break;
            case WhereOperator::BETWEEN:
                $writer
                    ->append(" between $")
                    ->append($this->options->getFromParameterName())
                    ->append(" and $")
                    ->append($this->options->getToParameterName());
                break;
            case WhereOperator::SEARCH:
                $writer
                    ->append(", $")
                    ->append($this->parameterName);
                if ($this->options->getSearchOperator()->isAnd()) {
                    $writer->append(", and");
                }
                $writer->append(")");
                break;
            case WhereOperator::LUCENE:
            case WhereOperator::STARTS_WITH:
            case WhereOperator::ENDS_WITH:
            case WhereOperator::REGEX:
                $writer
                    ->append(", $")
                    ->append($this->parameterName)
                    ->append(")");
                break;
            case WhereOperator::EXISTS:
                $writer
                    ->append(")");
                break;
            case WhereOperator::SPATIAL_WITHIN:
            case WhereOperator::SPATIAL_CONTAINS:
            case WhereOperator::SPATIAL_DISJOINT:
            case WhereOperator::SPATIAL_INTERSECTS:
                $writer
                    ->append(", ");
                $this->options->getWhereShape()->writeTo($writer);

                if (abs($this->options->getDistanceErrorPct() - DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT) > 1e-40) {
                    $writer->append(", ");
                    $writer->append($this->options->getDistanceErrorPct());
                }
                $writer
                    ->append(")");
                break;
            default:
                throw new IllegalArgumentException();
        }
    }
}
