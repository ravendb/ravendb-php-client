<?php

namespace RavenDB\Primitives;

use DateTime;
use DateTimeInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\DateUtils;

class DatesComparator
{
    public static function compare(DateWithContext $lhs, DateWithContext $rhs): int
    {
        if ($lhs->getDate() != null && $rhs->getDate() != null) {
            if (DateUtils::ensureMilliseconds($lhs->getDate()) == DateUtils::ensureMilliseconds($rhs->getDate())) {
                return 0;
            }
            return $lhs->getDate() > $rhs->getDate() ? 1 : -1;
        }

        // lhr or rhs is null - unify values using context
        $leftValue = $lhs->getDate() != null
                ? $lhs->getDate()->getTimestamp()
                : ($lhs->getContext() == DateContext::From ? PHP_INT_MIN : PHP_INT_MAX);

        $rightValue = $rhs->getDate() != null
                ? $rhs->getDate()->getTimestamp()
                : ($rhs->getContext() == DateContext::From ? PHP_INT_MIN : PHP_INT_MAX);


        if ($leftValue == $rightValue) {
            return 0;
        }
        return $leftValue > $rightValue ? 1 : -1;
    }

    /*
      Date or MinDate if null
     */
    public static function leftDate(?DateTimeInterface $date): DateWithContext
    {
        return new DateWithContext($date, DateContext::From);
    }

    /*
     Date or MaxDate if null
     */
    public static function rightDate(?DateTimeInterface $date): DateWithContext
    {
        return new DateWithContext($date, DateContext::To);
    }

    public static function definedDate(?DateTimeInterface $date): DateWithContext
    {
        if ($date == null) {
            throw new IllegalArgumentException("Date cannot be null");
        }

        return new DateWithContext($date, DateContext::To);
    }
}
