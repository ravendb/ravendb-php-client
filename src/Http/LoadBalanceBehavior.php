<?php

namespace RavenDB\Http;

use RavenDB\Exceptions\InvalidValueException;
use RavenDB\Type\ValueObjectInterface;

class LoadBalanceBehavior implements ValueObjectInterface
{
    const NONE = 'NONE';
    const USE_SESSION_CONTEXT = 'USE_SESSION_CONTEXT';

    const NAME = 'LoadBalanceBehavior';

    private string $value;

    /**
     * @throws InvalidValueException
     */
    public function __construct(?string $value = null)
    {
        if ($value === null) {
            $value = self::NONE;
        }

        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->value;
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
        return $this->value == self::USE_SESSION_CONTEXT;
    }

    public function isNone(): bool
    {
        return $this->value == self::NONE;
    }

    public static function allValues(): array
    {
        return [
            self::NONE,
            self::USE_SESSION_CONTEXT
        ];
    }

    public static function none(): LoadBalanceBehavior
    {
        return new LoadBalanceBehavior(LoadBalanceBehavior::NONE);
    }

    public static function useSessionContext(): LoadBalanceBehavior
    {
        return new LoadBalanceBehavior(LoadBalanceBehavior::USE_SESSION_CONTEXT);
    }
}
