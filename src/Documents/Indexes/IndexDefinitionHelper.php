<?php

namespace RavenDB\Documents\Indexes;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class IndexDefinitionHelper
{
  public static function detectStaticIndexType(string $map, ?string $reduce): IndexType
  {
        if (StringUtils::isEmpty($map)) {
            throw new IllegalArgumentException("Index definitions contains no Maps");
        }

        $map = self::stripComments($map);
        $map = self::unifyWhiteSpace($map);

        $mapLower = strtolower($map);
        if (
            str_starts_with($mapLower, 'from') ||
            str_starts_with($mapLower, 'docs') ||
            (str_starts_with($mapLower, 'timeseries') && !str_starts_with($mapLower, 'timeseries.map')) ||
            (str_starts_with($mapLower, 'counters') && !str_starts_with($mapLower, 'counters.map'))
        ) {
            // C# indexes must start with "from" for query syntax or
            // "docs" for method syntax
            if ($reduce == null || StringUtils::isBlank($reduce)) {
                return IndexType::map();
            }
            return IndexType::mapReduce();
        }

        if (StringUtils::isBlank($reduce)) {
            return IndexType::javaScriptMap();
        }

        return IndexType::javaScriptMapReduce();
    }

    public static function detectStaticIndexSourceType(?string $map): IndexSourceType
    {
        if (StringUtils::isBlank($map)) {
            throw new IllegalArgumentException("Value cannot be null or whitespace.");
        }

        $map = self::stripComments($map);
        $map = self::unifyWhiteSpace($map);

        // detect first supported syntax: timeseries.Companies.HeartRate.Where
        $mapLower = strtolower($map);
        if (str_starts_with($mapLower, "timeseries")) {
            return IndexSourceType::timeSeries();
        }

        if (str_starts_with($mapLower, "counters")) {
            return IndexSourceType::counters();
        }

        if (str_starts_with($map, "from")) {
            // detect `from ts in timeseries` or `from ts in timeseries.Users.HeartRate`

            $tokens = [];
            foreach (explode(' ', $mapLower) as $item) {
                if (StringUtils::isNotEmpty($item)) {
                    $tokens[] = $item;
                }
            }

            if (count($tokens) >= 4 && strcasecmp($tokens[2], "in") == 0) {
                if (str_starts_with($tokens[3], "timeseries")) {
                    return IndexSourceType::timeSeries();
                }
                if (str_starts_with($tokens[3], "counters")) {
                    return IndexSourceType::counters();
                }
            }
        }

        // fallback to documents based index
        return IndexSourceType::documents();
    }

    private static function stripComments(string $input): string
    {
        return trim(preg_replace('~(?:/\\*(?:[^*]|(?:\\*+[^*/]))*\\*+/)|(?://.*)~',"", $input));
    }

    private static function unifyWhiteSpace(string $input): string
    {
        return preg_replace("/\s+/", " ", $input);
    }
}
