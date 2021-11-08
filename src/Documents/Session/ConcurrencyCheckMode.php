<?php

namespace RavenDB\Documents\Session;

class ConcurrencyCheckMode
{
    /**
     * Automatic optimistic concurrency check depending on UseOptimisticConcurrency setting or provided Change Vector
     */
    private const AUTO = 'auto';

    /**
     * Force optimistic concurrency check even if UseOptimisticConcurrency is not set
     */
    private const FORCED = 'forced';

    /**
     * Disable optimistic concurrency check even if UseOptimisticConcurrency is set
     */
    private const DISABLED = 'disabled';

    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public function isAuto(): bool
    {
        return ConcurrencyCheckMode::AUTO === $this->value;
    }

    public function isForced(): bool
    {
        return ConcurrencyCheckMode::FORCED === $this->value;
    }

    public function isDisabled(): bool
    {
        return ConcurrencyCheckMode::DISABLED === $this->value;
    }

    public static function auto(): ConcurrencyCheckMode
    {
        return new ConcurrencyCheckMode(ConcurrencyCheckMode::AUTO);
    }

    public static function forced(): ConcurrencyCheckMode
    {
        return new ConcurrencyCheckMode(ConcurrencyCheckMode::FORCED);
    }

    public static function disabled(): ConcurrencyCheckMode
    {
        return new ConcurrencyCheckMode(ConcurrencyCheckMode::DISABLED);
    }
}
