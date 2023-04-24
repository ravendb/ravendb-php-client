<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use RavenDB\Constants\PhpClient;
use Symfony\Component\Serializer\Annotation\SerializedName;

class MoreLikeThisOptions
{
    public const DEFAULT_MAXIMUM_NUMBER_OF_TOKENS_PARSED = 5000;
    public const DEFAULT_MINIMUM_TERM_FREQUENCY = 2;
    public const DEFAULT_MINIMUM_DOCUMENT_FREQUENCY = 5;
    public const DEFAULT_MAXIMUM_DOCUMENT_FREQUENCY = PhpClient::INT_MAX_VALUE;
    public const DEFAULT_BOOST = false;
    public const DEFAULT_BOOST_FACTOR = 1;
    public const DEFAULT_MINIMUM_WORD_LENGTH = 0;
    public const DEFAULT_MAXIMUM_WORD_LENGTH = 0;
    public const DEFAULT_MAXIMUM_QUERY_TERMS = 25;

    #[SerializedName("MinimumTermFrequency")]
    private ?int $minimumTermFrequency = null;
    #[SerializedName("MaximumQueryTerms")]
    private ?int $maximumQueryTerms = null;
    #[SerializedName("MaximumNumberOfTokensParsed")]
    private ?int $maximumNumberOfTokensParsed = null;
    #[SerializedName("MinimumWordLength")]
    private ?int $minimumWordLength = null;
    #[SerializedName("MaximumWordLength")]
    private ?int $maximumWordLength = null;
    #[SerializedName("MinimumDocumentFrequency")]
    private ?int $minimumDocumentFrequency = null;
    #[SerializedName("MaximumDocumentFrequency")]
    private ?int $maximumDocumentFrequency = null;
    #[SerializedName("MaximumDocumentFrequencyPercentage")]
    private ?int $maximumDocumentFrequencyPercentage = null;
    #[SerializedName("Boost")]
    private ?bool $boost = null;
    #[SerializedName("BoostFactor")]
    private ?float $boostFactor = null;
    #[SerializedName("StopWordsDocumentId")]
    private ?string $stopWordsDocumentId = null;
    #[SerializedName("Fields")]
    private ?array $fields = null;

    private static ?MoreLikeThisOptions $defaultOptions = null;

    public static function defaultOptions(): MoreLikeThisOptions
    {
        if (self::$defaultOptions == null) {
            self::$defaultOptions = new MoreLikeThisOptions();
        }

        return self::$defaultOptions;
    }

    /**
     * @return int|null Ignore terms with less than this frequency in the source doc. Default is 2.
     */
    public function getMinimumTermFrequency(): ?int
    {
        return $this->minimumTermFrequency;
    }

    /**
     * @param int|null $minimumTermFrequency    Ignore terms with less than this frequency in the source doc. Default is 2.
     */
    public function setMinimumTermFrequency(?int $minimumTermFrequency): void
    {
        $this->minimumTermFrequency = $minimumTermFrequency;
    }

    /**
     * @return int|null Return a Query with no more than this many terms. Default is 25.
     */
    public function getMaximumQueryTerms(): ?int
    {
        return $this->maximumQueryTerms;
    }

    /**
     * @param int|null $maximumQueryTerms   Return a Query with no more than this many terms. Default is 25.
     */
    public function setMaximumQueryTerms(?int $maximumQueryTerms): void
    {
        $this->maximumQueryTerms = $maximumQueryTerms;
    }

    /**
     * @return int|null The maximum number of tokens to parse in each example doc field that is not stored with TermVector support. Default is 5000.
     */
    public function getMaximumNumberOfTokensParsed(): ?int
    {
        return $this->maximumNumberOfTokensParsed;
    }

    /**
     * @param int|null $maximumNumberOfTokensParsed The maximum number of tokens to parse in each example doc field that is not stored with TermVector support. Default is 5000.
     */
    public function setMaximumNumberOfTokensParsed(?int $maximumNumberOfTokensParsed): void
    {
        $this->maximumNumberOfTokensParsed = $maximumNumberOfTokensParsed;
    }

    /**
     * @return int|null Ignore words less than this length or if 0 then this has no effect. Default is 0.
     */
    public function getMinimumWordLength(): ?int
    {
        return $this->minimumWordLength;
    }

    /**
     * @param int|null $minimumWordLength   Ignore words less than this length or if 0 then this has no effect. Default is 0.
     */
    public function setMinimumWordLength(?int $minimumWordLength): void
    {
        $this->minimumWordLength = $minimumWordLength;
    }

    /**
     * @return int|null Ignore words greater than this length or if 0 then this has no effect. Default is 0.
     */
    public function getMaximumWordLength(): ?int
    {
        return $this->maximumWordLength;
    }

    /**
     * @param int|null $maximumWordLength   Ignore words greater than this length or if 0 then this has no effect. Default is 0.
     */
    public function setMaximumWordLength(?int $maximumWordLength): void
    {
        $this->maximumWordLength = $maximumWordLength;
    }

    /**
     * @return int|null Ignore words which do not occur in at least this many documents. Default is 5.
     */
    public function getMinimumDocumentFrequency(): ?int
    {
        return $this->minimumDocumentFrequency;
    }

    /**
     * @param int|null $minimumDocumentFrequency    Ignore words which do not occur in at least this many documents. Default is 5.
     */
    public function setMinimumDocumentFrequency(?int $minimumDocumentFrequency): void
    {
        $this->minimumDocumentFrequency = $minimumDocumentFrequency;
    }

    /**
     * @return int|null Ignore words which occur in more than this many documents. Default is Int32.MaxValue.
     */
    public function getMaximumDocumentFrequency(): ?int
    {
        return $this->maximumDocumentFrequency;
    }

    /**
     * @param int|null $maximumDocumentFrequency    Ignore words which occur in more than this many documents. Default is Int32.MaxValue.
     */
    public function setMaximumDocumentFrequency(?int $maximumDocumentFrequency): void
    {
        $this->maximumDocumentFrequency = $maximumDocumentFrequency;
    }

    /**
     * @return int|null Ignore words which occur in more than this percentage of documents.
     */
    public function getMaximumDocumentFrequencyPercentage(): ?int
    {
        return $this->maximumDocumentFrequencyPercentage;
    }

    /**
     * @param int|null $maximumDocumentFrequencyPercentage Ignore words which occur in more than this percentage of documents.
     */
    public function setMaximumDocumentFrequencyPercentage(?int $maximumDocumentFrequencyPercentage): void
    {
        $this->maximumDocumentFrequencyPercentage = $maximumDocumentFrequencyPercentage;
    }

    /**
     * @return ?bool Boost terms in query based on score. Default is false.
     */
    public function isBoost(): ?bool
    {
        return $this->boost;
    }

    /**
     * @param ?bool $boost Boost terms in query based on score. Default is false.
     */
    public function setBoost(?bool $boost): void
    {
        $this->boost = $boost;
    }

    /**
     * @return float|null Boost factor when boosting based on score. Default is 1.
     */
    public function getBoostFactor(): ?float
    {
        return $this->boostFactor;
    }

    /**
     * @param float|null $boostFactor Boost factor when boosting based on score. Default is 1.
     */
    public function setBoostFactor(?float $boostFactor): void
    {
        $this->boostFactor = $boostFactor;
    }

    /**
     * @return string|null The document id containing the custom stop words
     */
    public function getStopWordsDocumentId(): ?string
    {
        return $this->stopWordsDocumentId;
    }

    /**
     * @param string|null $stopWordsDocumentId The document id containing the custom stop words
     */
    public function setStopWordsDocumentId(?string $stopWordsDocumentId): void
    {
        $this->stopWordsDocumentId = $stopWordsDocumentId;
    }

    /**
     * The fields to compare
     *
     * @return array|null
     */
    public function getFields(): ?array
    {
        return $this->fields;
    }

    /**
     * @param array|null $fields The fields to compare
     */
    public function setFields(?array $fields): void
    {
        $this->fields = $fields;
    }
}
