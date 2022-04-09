<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Constants\DocumentIndexingFields;

class QueryFieldUtil
{
    private static function shouldEscape(string $s, bool $isPath): bool
    {
        $escape = false;
        $insideEscaped = false;
//
//        for (int i = 0; i < s.length(); i++) {
//            char c = s.charAt(i);
//
//            if (c == '\'' || c == '"') {
//                insideEscaped = !insideEscaped;
//                continue;
//            }
//
//            if (i == 0) {
//                if (!Character.isLetter(c) && c != '_' && c != '@' && !insideEscaped) {
//                    escape = true;
//                    break;
//                }
//            } else {
//                if (!Character.isLetterOrDigit(c) && c != '_' && c != '-' && c != '@' && c != '.' && c != '[' && c != ']' && !insideEscaped) {
//                    escape = true;
//                    break;
//                }
//                if (isPath && c == '.' && !insideEscaped) {
//                    escape = true;
//                    break;
//                }
//            }
//        }

        $escape |= $insideEscaped;
        return $escape;
    }

    public static function escapeIfNecessary(string $name, bool $isPath = false): string
    {
        if (empty($name) || in_array($name, [
                DocumentIndexingFields::DOCUMENT_ID_FIELD_NAME,
                DocumentIndexingFields::REDUCE_KEY_HASH_FIELD_NAME,
                DocumentIndexingFields::REDUCE_KEY_KEY_VALUE_FIELD_NAME,
                DocumentIndexingFields::VALUE_FIELD_NAME,
                DocumentIndexingFields::SPATIAL_SHAPE_FIELD_NAME
            ])) {
            return $name;
        }

        if (!self::shouldEscape($name, $isPath)) {
            return $name;
        }

//        StringBuilder sb = new StringBuilder(name);
        $needEndQuote = false;
        $lastTermStart = 0;

//        for (int i = 0; i < sb.length(); i++) {
//            char c = sb.charAt(i);
//            if (i == 0 && !Character.isLetter(c) && c != '_' && c != '@') {
//                sb.insert(lastTermStart, '\'');
//                needEndQuote = true;
//                continue;
//            }
//
//            if (isPath && c == '.') {
//                if (needEndQuote) {
//                    needEndQuote = false;
//                    sb.insert(i, '\'');
//                    i++;
//                }
//
//                lastTermStart = i + 1;
//                continue;
//            }
//
//            if (!Character.isLetterOrDigit(c) && c != '_' && c != '-' && c != '@' && c != '.' && c != '[' && c != ']' && !needEndQuote) {
//                sb.insert(lastTermStart, '\'');
//                needEndQuote = true;
//                continue;
//            }
//        }
//
//        if (needEndQuote) {
//            sb.append('\'');
//        }
//
//        return sb.toString();
    }
}
