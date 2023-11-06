<?php

namespace RavenDB\Documents\Session\Tokens;

use DateTime;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

class RevisionIncludesToken
{
    private ?string $dateTime = null;
    private ?string $path = null;

    protected function __construct()
    {
    }

    public static function createFromDateTime(DateTime $dateTime): RevisionIncludesToken
    {
        $token = new RevisionIncludesToken();
        $token->dateTime = NetISO8601Utils::format($dateTime, true);

        return $token;
    }

    public static function createFromChangeVector(string $path): RevisionIncludesToken
    {
        $token = new RevisionIncludesToken();
        $token->path = $path;

        return $token;
    }

    public function writeTo(StringBuilder &$writer): void
    {
        $writer->append("revisions('");

        if ($this->dateTime != null) {
            $writer->append($this->dateTime);
        } else if (StringUtils::isNotEmpty($this->path)) {
            $writer->append($this->path);
        }
        $writer->append("')");
    }
}
