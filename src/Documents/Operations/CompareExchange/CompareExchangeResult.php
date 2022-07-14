<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Http\ResultInterface;
use RavenDB\Constants\CompareExchange;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Conventions\DocumentConventions;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @template T
 */
class CompareExchangeResult implements ResultInterface
{
    /** @var ?T */
    private $value = null;

    private ?int $index = null;

    private bool $successful = false;

    /**
     * @param string                   $className
     * @param string|null              $responseString
     * @param DocumentConventions|null $conventions
     *
     * @return CompareExchangeResult<T>
     *
     * @throws ExceptionInterface
     */
    public static function parseFromString(
        ?string $className,
        ?string $responseString,
        ?DocumentConventions $conventions
    ): CompareExchangeResult {
        $response = json_decode($responseString, true);

        if (!array_key_exists('Index', $response) || empty($response['Index'])) {
            throw new IllegalStateException("Response is invalid. Index is missing");
        }

        $indexJson = $response["Index"];

        $index = intval($indexJson);

        $successful = boolval($response["Successful"]);
        $raw = $response["Value"];

        $val = null;

        if (!empty($raw)) {
            $val = array_key_exists(CompareExchange::OBJECT_FIELD_NAME, $raw) ? $raw[CompareExchange::OBJECT_FIELD_NAME] : null;
        }

        if ($val == null) {
            $exchangeResult = new CompareExchangeResult();
            $exchangeResult->index = $index;
            $exchangeResult->value = null;
            $exchangeResult->successful = $successful;
            return $exchangeResult;
        }

        $result = ($className == null) || !is_array($val)
                ? $val
                : $conventions->getEntityMapper()->denormalize($val, $className);

        $exchangeResult = new CompareExchangeResult();
        $exchangeResult->index = $index;
        $exchangeResult->value = $result;
        $exchangeResult->successful = $successful;
        return $exchangeResult;

    }


    /**
     * @return ?T
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param ?T $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(?int $index): void
    {
        $this->index = $index;
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function setSuccessful(bool $successful): void
    {
        $this->successful = $successful;
    }
}
