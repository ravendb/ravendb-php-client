<?php

namespace RavenDB\Documents\Queries;

class IndexQueryWithParameters extends IndexQueryBase
{
    private bool $skipDuplicateChecking = false;

    /**
     * Allow to skip duplicate checking during queries
     * @return bool true if server can skip duplicate checking
     */
    public function isSkipDuplicateChecking(): bool
    {
        return $this->skipDuplicateChecking;
    }

    /**
     * Allow to skip duplicate checking during queries
     * @param bool $skipDuplicateChecking sets the value
     */
    public function setSkipDuplicateChecking(bool $skipDuplicateChecking): void
    {
        $this->skipDuplicateChecking = $skipDuplicateChecking;
    }

    public function equals(?object &$o): bool
    {
        if ($this == $o) return true;
        if (($o == null) || (get_class($this) != get_class($o))) return false;
        if (!parent::equals($o)) return false;

        /** @var IndexQueryWithParameters $that */
        $that = $o;

        return $this->skipDuplicateChecking == $that->skipDuplicateChecking;
    }

    public function hashCode(): int
    {
        $result = parent::hashCode();
        return 31 * $result + ($this->skipDuplicateChecking ? 1 : 0);
    }
}
