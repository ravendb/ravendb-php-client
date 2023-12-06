<?php

namespace RavenDB\Documents\Queries\Facets;

use Symfony\Component\Serializer\Annotation\SerializedName;

class FacetOptions
{
    private static ?FacetOptions $defaultOptions = null;

    /** @SerializedName("TermSortMode") */
    private FacetTermSortMode $termSortMode;

    /** @SerializedName("IncludeRemainingTerms") */
    private bool $includeRemainingTerms;

    /** @SerializedName("Start") */
    private int $start = 0;

    /** @SerializedName("PageSize") */
    private int $pageSize = 0;

    private const INT_32_MAX = 2147483647;

    public function __construct() {
        $this->pageSize = self::INT_32_MAX;
        $this->termSortMode = FacetTermSortMode::valueAsc();
    }

    public static function getDefaultOptions(): FacetOptions
    {
        if (self::$defaultOptions == null) {
            self::$defaultOptions = new FacetOptions();
        }

        return self::$defaultOptions;
    }

    public function getTermSortMode(): FacetTermSortMode
    {
        return $this->termSortMode;
    }

    public function setTermSortMode(FacetTermSortMode $termSortMode): void
    {
        $this->termSortMode = $termSortMode;
    }

    /**
     * @return bool Indicates if remaining terms should be included in results.
     */
    public function isIncludeRemainingTerms(): bool
    {
        return $this->includeRemainingTerms;
    }

    /**
     * @param bool $includeRemainingTerms Indicates if remaining terms should be included in results.
     */
    public function setIncludeRemainingTerms(bool $includeRemainingTerms): void
    {
        $this->includeRemainingTerms = $includeRemainingTerms;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function setStart(int $start): void
    {
        $this->start = $start;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function setPageSize(int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }
}
