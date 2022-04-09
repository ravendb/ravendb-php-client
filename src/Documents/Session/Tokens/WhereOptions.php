<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Queries\SearchOperator;

class WhereOptions
{
        private ?SearchOperator $searchOperator = null;
//        private String fromParameterName;
//        private String toParameterName;
//        private Double boost;
//        private Double fuzzy;
//        private Integer proximity;
//        private boolean exact;
//        private WhereMethodCall method;
//        private ShapeToken whereShape;
//        private double distanceErrorPct;
//
        public static function defaultOptions(): WhereOptions
        {
            return new WhereOptions();
        }
//
//        private WhereOptions() {
//        }
//
//        public WhereOptions(boolean exact) {
//            this.exact = exact;
//        }
//
//        public WhereOptions(boolean exact, String from, String to) {
//            this.exact = exact;
//            this.fromParameterName = from;
//            this.toParameterName = to;
//        }
//
//        public WhereOptions(SearchOperator search) {
//            this.searchOperator = search;
//        }
//
//        public WhereOptions(ShapeToken shape, double distance) {
//            whereShape = shape;
//            distanceErrorPct = distance;
//        }
//
//        public WhereOptions(MethodsType methodType, String[] parameters, String property) {
//            this(methodType, parameters, property, false);
//        }
//
//        public WhereOptions(MethodsType methodType, String[] parameters, String property, boolean exact) {
//            method = new WhereMethodCall();
//            method.methodType = methodType;
//            method.parameters = parameters;
//            method.property = property;
//
//            this.exact = exact;
//        }

        public function getSearchOperator(): SearchOperator
        {
            return $this->searchOperator;
        }

        public function setSearchOperator(SearchOperator $searchOperator): void
        {
            $this->searchOperator = $searchOperator;
        }

//        public String getFromParameterName() {
//            return fromParameterName;
//        }
//
//        public void setFromParameterName(String fromParameterName) {
//            this.fromParameterName = fromParameterName;
//        }
//
//        public String getToParameterName() {
//            return toParameterName;
//        }
//
//        public void setToParameterName(String toParameterName) {
//            this.toParameterName = toParameterName;
//        }
//
//        public Double getBoost() {
//            return boost;
//        }
//
//        public void setBoost(Double boost) {
//            this.boost = boost;
//        }
//
//        public Double getFuzzy() {
//            return fuzzy;
//        }
//
//        public void setFuzzy(Double fuzzy) {
//            this.fuzzy = fuzzy;
//        }
//
//        public Integer getProximity() {
//            return proximity;
//        }
//
//        public void setProximity(Integer proximity) {
//            this.proximity = proximity;
//        }
//
//        public boolean isExact() {
//            return exact;
//        }
//
//        public void setExact(boolean exact) {
//            this.exact = exact;
//        }
//
//        public WhereMethodCall getMethod() {
//            return method;
//        }
//
//        public void setMethod(WhereMethodCall method) {
//            this.method = method;
//        }
//
//        public ShapeToken getWhereShape() {
//            return whereShape;
//        }
//
//        public void setWhereShape(ShapeToken whereShape) {
//            this.whereShape = whereShape;
//        }
//
//        public double getDistanceErrorPct() {
//            return distanceErrorPct;
//        }
//
//        public void setDistanceErrorPct(double distanceErrorPct) {
//            this.distanceErrorPct = distanceErrorPct;
//        }
//
}
