<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Type\ValueObjectInterface;

class DatabasePromotionStatus implements ValueObjectInterface
{
    private const WAITING_FOR_FIRST_PROMOTION = 'WaitingForFirstPromotion';
    private const NOT_RESPONDING = 'NotResponding';
    private const INDEX_NOT_UP_TO_DATE = 'IndexNotUpToDate';
    private const CHANGE_VECTOR_NOT_MERGED = 'ChangeVectorNotMerged';
    private const WAITING_FOR_RESPONSE = 'WaitingForResponse';
    private const OK = 'Ok';
    private const OUT_OF_CPU_CREDITS = 'OutOfCpuCredits';
    private const EARLY_OUT_OF_MEMORY = 'EarlyOutOfMemory';
    private const HIGH_DIRTY_MEMORY = 'HighDirtyMemory';

    private string $value;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isWaitingForFirstPromotion(): bool
    {
        return $this->value == self::WAITING_FOR_FIRST_PROMOTION;
    }

    public static function waitingForFirstPromotion(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::WAITING_FOR_FIRST_PROMOTION);
    }

    public function isNotResponding(): bool
    {
        return $this->value == self::NOT_RESPONDING;
    }

    public static function notResponding(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::NOT_RESPONDING);
    }

    public function isIndexNotUpToDate(): bool
    {
        return $this->value == self::INDEX_NOT_UP_TO_DATE;
    }

    public static function indexNotUpToDate(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::INDEX_NOT_UP_TO_DATE);
    }

    public function isChangeVectorNotMerged(): bool
    {
        return $this->value == self::CHANGE_VECTOR_NOT_MERGED;
    }

    public static function changeVectorNotMerged(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::CHANGE_VECTOR_NOT_MERGED);
    }

    public function isWaitingForResponse(): bool
    {
        return $this->value == self::WAITING_FOR_RESPONSE;
    }

    public static function waitingForResponse(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::WAITING_FOR_RESPONSE);
    }

    public function isOk(): bool
    {
        return $this->value == self::OK;
    }

    public static function ok(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::OK);
    }

    public function isOutOfCpuCredits(): bool
    {
        return $this->value == self::OUT_OF_CPU_CREDITS;
    }

    public static function outOfCpuCredits(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::OUT_OF_CPU_CREDITS);
    }

    public function isEarlyOutOfMemory(): bool
    {
        return $this->value == self::EARLY_OUT_OF_MEMORY;
    }

    public static function earlyOutOfMemory(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::EARLY_OUT_OF_MEMORY);
    }

    public function isHighDirtyMemory(): bool
    {
        return $this->value == self::HIGH_DIRTY_MEMORY;
    }

    public static function highDirtyMemory(): DatabasePromotionStatus
    {
        return new DatabasePromotionStatus(self::HIGH_DIRTY_MEMORY);
    }
}
