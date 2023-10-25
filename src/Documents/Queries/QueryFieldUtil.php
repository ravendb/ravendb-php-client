<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Utils\StringBuilder;

class QueryFieldUtil
{
    private static function shouldEscape(string $s, bool $isPath): bool
    {
        $escape = false;
        $insideEscaped = false;

        for ($i = 0; $i < strlen($s); $i++) {
            $c = $s[$i];

            if ($c == '\'' || $c == '"') {
                $insideEscaped = !$insideEscaped;
                continue;
            }

            if ($i == 0) {

                if (!ctype_alpha($c) && $c != '_' && $c != '@' && !$insideEscaped) {
                    $escape = true;
                    break;
                }
            } else {
                if (!ctype_alnum($c) && $c != '_' && $c != '-' && $c != '@' && $c != '.' && $c != '[' && $c != ']' && !$insideEscaped) {
                    $escape = true;
                    break;
                }
                if ($isPath && $c == '.' && !$insideEscaped) {
                    $escape = true;
                    break;
                }
            }
        }

        $escape |= $insideEscaped;
        return $escape;
    }

    public static function escapeIfNecessary(?string $name, bool $isPath = false): ?string
    {
        if (empty($name) || in_array($name, [
                DocumentsIndexingFields::DOCUMENT_ID_FIELD_NAME,
                DocumentsIndexingFields::REDUCE_KEY_HASH_FIELD_NAME,
                DocumentsIndexingFields::REDUCE_KEY_KEY_VALUE_FIELD_NAME,
                DocumentsIndexingFields::VALUE_FIELD_NAME,
                DocumentsIndexingFields::SPATIAL_SHAPE_FIELD_NAME
            ])) {
            return $name;
        }

        if (!self::shouldEscape($name, $isPath)) {
            return $name;
        }

        $sb = new StringBuilder($name);
        $needEndQuote = false;
        $lastTermStart = 0;

        for ($i = 0; $i < strlen($sb); $i++) {
            $c = $sb[$i];
            if ($i == 0 && !ctype_alpha($c) && $c != '_' && $c != '@') {
                $sb->insert($lastTermStart, '\'');
                $needEndQuote = true;
                continue;
            }

            if ($isPath && $c == '.') {
                if ($needEndQuote) {
                    $needEndQuote = false;
                    $sb->insert($i, '\'');
                    $i++;
                }

                $lastTermStart = $i + 1;
                continue;
            }

            if (!ctype_alnum($c) && $c != '_' && $c != '-' && $c != '@' && $c != '.' && $c != '[' && $c != ']' && !$needEndQuote) {
                $sb->insert($lastTermStart, '\'');
                $needEndQuote = true;
                continue;
            }
        }

        if ($needEndQuote) {
            $sb->append('\'');
        }

        return $sb->__toString();
    }
}
