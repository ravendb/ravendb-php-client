<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\InvalidValueException;

class LoadBalanceBehavior
{
    const None = 'NONE';
    const UseSessionContext = 'USE_SESSION_CONTEXT';

    const NAME = 'LoadBalanceBehavior';

    private string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(string $value)
    {
        $this->setValue($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @throws InvalidValueException
     */
    public function setValue(string $value): void
    {
        $upperCaseValue = strtoupper($value);
        $this->validate($value);
        $this->value = $upperCaseValue;
    }

    /**
     * @throws InvalidValueException
     */
    private function validate(string $value)
    {
        if (!in_array($value, self::allValues())) {
            throw new InvalidValueException(self::NAME, $value);
        }
    }

    public function isUseSessionContext(): bool
    {
        return $this->value == self::UseSessionContext;
    }

    public function isNone(): bool
    {
        return $this->value == self::None;
    }

    public static function allValues(): array
    {
        return [
            self::None,
            self::UseSessionContext
        ];
    }

    public static function none(): LoadBalanceBehavior
    {
        return new LoadBalanceBehavior(LoadBalanceBehavior::None);
    }

    public static function useSessionContext(): LoadBalanceBehavior
    {
        return new LoadBalanceBehavior(LoadBalanceBehavior::UseSessionContext);
    }
}
