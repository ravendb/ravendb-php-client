<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Documents\Session\DocumentQueryHelper;
use RavenDB\Utils\StringBuilder;

class MoreLikeThisToken extends WhereToken
{
  public ?string $documentParameterName = null;

    public ?string $optionsParameterName = null;

    public QueryTokenList $whereTokens;

    public function __construct() {
        $this->whereTokens = new QueryTokenList();
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("moreLikeThis(");

        if ($this->documentParameterName == null) {
            $prevToken = null;
            foreach ($this->whereTokens as $currentToken) {
                DocumentQueryHelper::addSpaceIfNeeded($prevToken, $currentToken, $writer);
                $currentToken->writeTo($writer);
                $prevToken = $currentToken;
            }
        } else {
            $writer->append("$")
                    ->append($this->documentParameterName);
        }

        if ($this->optionsParameterName == null) {
            $writer->append(")");
            return;
        }

        $writer->append(", $")
            ->append($this->optionsParameterName)
            ->append(")");
    }
}
