<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Session\Tokens\CloseSubclauseToken;
use RavenDB\Documents\Session\Tokens\IntersectMarkerToken;
use RavenDB\Documents\Session\Tokens\OpenSubclauseToken;
use RavenDB\Documents\Session\Tokens\QueryToken;
use RavenDB\Utils\StringBuilder;

class DocumentQueryHelper
{
    public static function addSpaceIfNeeded(
        ?QueryToken $previousToken,
        ?QueryToken $currentToken,
        StringBuilder &$writer) {

        if ($previousToken == null) {
            return;
        }

        if (($previousToken instanceof OpenSubclauseToken) ||
            ($currentToken instanceof CloseSubclauseToken) ||
            ($currentToken instanceof IntersectMarkerToken)) {
            return;
        }

        $writer->append(" ");
    }
}
