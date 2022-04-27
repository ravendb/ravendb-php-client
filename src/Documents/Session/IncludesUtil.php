<?php

namespace RavenDB\Documents\Session;

class IncludesUtil
{
    public static function include(array $document, string $include, $loadId): void
    {
        if (empty($include) || empty($document)) {
            return;
        }

        // TBD
    }

    /* TBD
     public static void Include(BlittableJsonReaderObject document, string include, Action<string> loadId)
        {
            if (string.IsNullOrEmpty(include) || document == null)
                return;
            var path = GetIncludePath(include, out var isPrefix);

            foreach (var token in document.SelectTokenWithRavenSyntaxReturningFlatStructure(path.Path))
            {
                ExecuteInternal(token.Item1, path.Addition, (value, addition) =>
                {
                    value = addition != null
                        ? (isPrefix ? addition + value : string.Format(addition, value))
                        : value;

                    loadId(value);
                });
            }
        }
     */

    public static function requiresQuotes(string $include, ?string &$escapedInclude): bool
    {
        $length = strlen($include);
        for ($i = 0; $i < $length; $i++) {
            $char = $include[$i];
            if (!ctype_alnum($char) && ($char != '_') && ($char != '.')) {
                $escapedInclude = str_replace("'", "\\'", $include);
                $required = true;
            }
        }

        $escapedInclude = null;
        return false;
    }
}
