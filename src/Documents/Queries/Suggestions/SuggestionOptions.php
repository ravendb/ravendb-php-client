<?php

namespace RavenDB\Documents\Queries\Suggestions;

class SuggestionOptions
{
    private static ?SuggestionOptions $defaultOptions = null;

    public static function & defaultOptions(): SuggestionOptions
    {
        if (self::$defaultOptions == null) {
            self::$defaultOptions = new SuggestionOptions();
        }

        return self::$defaultOptions;
    }

    public const DEFAULT_ACCURACY = 0.5;

    public const DEFAULT_PAGE_SIZE = 15;

    public const DEFAULT_DISTANCE = StringDistanceTypes::LEVENSHTEIN;

    public const DEFAULT_SORT_MODE = SuggestionSortMode::POPULARITY;

    private ?int $pageSize = null;
    private ?StringDistanceTypes $distance = null;
    private ?float $accuracy = null;
    private ?SuggestionSortMode $sortMode = null;

    public function __construct()
    {
        $this->sortMode = new SuggestionSortMode(self::DEFAULT_SORT_MODE);
        $this->distance = new StringDistanceTypes(self::DEFAULT_DISTANCE);
        $this->accuracy = self::DEFAULT_ACCURACY;
        $this->pageSize = self::DEFAULT_PAGE_SIZE;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function setPageSize(?int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @return StringDistanceTypes|null String distance algorithm to use. If null then default algorithm is used (Levenshtein).
     */
    public function getDistance(): ?StringDistanceTypes
    {
        return $this->distance;
    }

    /**
     * @param StringDistanceTypes|null $distance String distance algorithm to use. If null then default algorithm is used (Levenshtein).
     */
    public function setDistance(?StringDistanceTypes $distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return float|null Suggestion accuracy. If null then default accuracy is used (0.5f).
     */
    public function getAccuracy(): ?float
    {
        return $this->accuracy;
    }

    /**
     * @param float|null $accuracy Suggestion accuracy. If null then default accuracy is used (0.5f).
     */
    public function setAccuracy(?float $accuracy): void
    {
        $this->accuracy = $accuracy;
    }

    /**
     * @return SuggestionSortMode|null Whether to return the terms in order of popularity
     */
    public function getSortMode(): ?SuggestionSortMode
    {
        return $this->sortMode;
    }

    /**
     * @param SuggestionSortMode|null $sortMode Whether to return the terms in order of popularity
     */
    public function setSortMode(?SuggestionSortMode $sortMode): void
    {
        $this->sortMode = $sortMode;
    }
}
