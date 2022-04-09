<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Utils\StringBuilder;

class WhereToken extends QueryToken
{
//  protected WhereToken() {
//    }
//
//    @UseSharpEnum
//    public enum MethodsType {
//        CMP_X_CHG
//    }
//
//    public static class WhereMethodCall {
//        public MethodsType methodType;
//        public String[] parameters;
//        public String property;
//    }

    private string $fieldName;
    private WhereOperator $whereOperator;
    private string $parameterName;
    private WhereOptions $options;

    public static function create(
        WhereOperator $op, string $fieldName, string $parameterName, ?WhereOptions $options = null
    ): WhereToken {
        $token = new WhereToken();
        $token->fieldName = $fieldName;
        $token->parameterName = $parameterName;
        $token->whereOperator = $op;
        $token->options = $options ?? WhereOptions::defaultOptions();
        return $token;
    }
//
//    public String getFieldName() {
//        return fieldName;
//    }
//
//    public void setFieldName(String fieldName) {
//        this.fieldName = fieldName;
//    }
//
//    public WhereOperator getWhereOperator() {
//        return whereOperator;
//    }
//
//    public void setWhereOperator(WhereOperator whereOperator) {
//        this.whereOperator = whereOperator;
//    }
//
//    public String getParameterName() {
//        return parameterName;
//    }
//
//    public void setParameterName(String parameterName) {
//        this.parameterName = parameterName;
//    }

    public function getOptions(): WhereOptions
    {
        return $this->options;
    }

    public function setOptions(WhereOptions $options): void
    {
        $this->options = $options;
    }

//    public WhereToken addAlias(String alias) {
//        if ("id()".equals(fieldName)) {
//            return this;
//        }
//
//        WhereToken whereToken = new WhereToken();
//        whereToken.setFieldName(alias + "." + fieldName);
//        whereToken.setParameterName(parameterName);
//        whereToken.setWhereOperator(whereOperator);
//        whereToken.setOptions(options);
//
//        return whereToken;
//    }
//
//    private boolean writeMethod(StringBuilder writer) {
//        if (options.getMethod() != null) {
//            switch (options.getMethod().methodType) {
//                case CMP_X_CHG:
//                    writer.append("cmpxchg(");
//                    break;
//                default:
//                    throw new IllegalArgumentException("Unsupported method: " + options.getMethod().methodType);
//            }
//
//            boolean first = true;
//            for (String parameter : options.getMethod().parameters) {
//                if (!first) {
//                    writer.append(",");
//                }
//                first = false;
//                writer.append("$");
//                writer.append(parameter);
//            }
//            writer.append(")");
//
//            if (options.getMethod().property != null) {
//                writer.append(".")
//                        .append(options.getMethod().property);
//            }
//            return true;
//        }
//
//        return false;
//    }
//

    public function writeTo(StringBuilder &$writer): void
    {
//        if (options.boost != null) {
//            writer.append("boost(");
//        }
//
//        if (options.fuzzy != null) {
//            writer.append("fuzzy(");
//        }
//
//        if (options.proximity != null) {
//            writer.append("proximity(");
//        }
//
//        if (options.exact) {
//            writer.append("exact(");
//        }
//
//        switch (whereOperator) {
//            case SEARCH:
//                writer.append("search(");
//                break;
//            case LUCENE:
//                writer.append("lucene(");
//                break;
//            case STARTS_WITH:
//                writer.append("startsWith(");
//                break;
//            case ENDS_WITH:
//                writer.append("endsWith(");
//                break;
//            case EXISTS:
//                writer.append("exists(");
//                break;
//            case SPATIAL_WITHIN:
//                writer.append("spatial.within(");
//                break;
//            case SPATIAL_CONTAINS:
//                writer.append("spatial.contains(");
//                break;
//            case SPATIAL_DISJOINT:
//                writer.append("spatial.disjoint(");
//                break;
//            case SPATIAL_INTERSECTS:
//                writer.append("spatial.intersects(");
//                break;
//            case REGEX:
//                writer.append("regex(");
//                break;
//        }
//
//        writeInnerWhere(writer);
//
//        if (options.exact) {
//            writer.append(")");
//        }
//
//        if (options.proximity != null) {
//            writer
//                    .append(", ")
//                    .append(options.proximity)
//                    .append(")");
//        }
//
//        if (options.fuzzy != null) {
//            writer
//                    .append(", ")
//                    .append(options.fuzzy)
//                    .append(")");
//        }
//
//        if (options.boost != null) {
//            writer
//                    .append(", ")
//                    .append(options.boost)
//                    .append(")");
//        }
    }
//
//    private void writeInnerWhere(StringBuilder writer) {
//
//        writeField(writer, fieldName);
//
//        switch (whereOperator) {
//            case EQUALS:
//                writer
//                        .append(" = ");
//                break;
//
//            case NOT_EQUALS:
//                writer
//                        .append(" != ");
//                break;
//            case GREATER_THAN:
//                writer
//                        .append(" > ");
//                break;
//            case GREATER_THAN_OR_EQUAL:
//                writer
//                        .append(" >= ");
//                break;
//            case LESS_THAN:
//                writer
//                        .append(" < ");
//                break;
//            case LESS_THAN_OR_EQUAL:
//                writer
//                        .append(" <= ");
//                break;
//            default:
//                specialOperator(writer);
//                return;
//        }
//
//        if (!writeMethod(writer)) {
//            writer.append("$").append(parameterName);
//        }
//    }
//
//    private void specialOperator(StringBuilder writer) {
//        switch (whereOperator)
//        {
//            case IN:
//                writer
//                        .append(" in ($")
//                        .append(parameterName)
//                        .append(")");
//                break;
//            case ALL_IN:
//                writer
//                        .append(" all in ($")
//                        .append(parameterName)
//                        .append(")");
//                break;
//            case BETWEEN:
//                writer
//                        .append(" between $")
//                        .append(options.fromParameterName)
//                        .append(" and $")
//                        .append(options.toParameterName);
//                break;
//
//            case SEARCH:
//                writer
//                        .append(", $")
//                        .append(parameterName);
//                if (options.searchOperator == SearchOperator.AND) {
//                    writer.append(", and");
//                }
//                writer.append(")");
//                break;
//            case LUCENE:
//            case STARTS_WITH:
//            case ENDS_WITH:
//            case REGEX:
//                writer
//                        .append(", $")
//                        .append(parameterName)
//                        .append(")");
//                break;
//            case EXISTS:
//                writer
//                        .append(")");
//                break;
//            case SPATIAL_WITHIN:
//            case SPATIAL_CONTAINS:
//            case SPATIAL_DISJOINT:
//            case SPATIAL_INTERSECTS:
//                writer
//                        .append(", ");
//                options.whereShape.writeTo(writer);
//
//                if (Math.abs(options.distanceErrorPct - Constants.Documents.Indexing.Spatial.DEFAULT_DISTANCE_ERROR_PCT) > 1e-40) {
//                    writer.append(", ");
//                    writer.append(options.distanceErrorPct);
//                }
//                writer
//                        .append(")");
//                break;
//            default:
//                throw new IllegalArgumentException();
//        }
//    }
}
