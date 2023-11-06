<?php

namespace RavenDB\Http;

use Brick\Math\Exception\NumberFormatException;
use RavenDB\Type\Url;
use RavenDB\Utils\StringUtils;

class ServerNode
{
    private ?Url $url = null;
    private ?string $database = null;
    private string $clusterTag;
    private ServerNodeRole $serverRole;

    public function __construct()
    {
        $this->serverRole = ServerNodeRole::none();
    }

    public function getUrl(): ?Url
    {
        return $this->url;
    }

    /**
     * @param Url|string|null $url
     */
    public function setUrl($url): void
    {
        $this->url = is_string($url) ? new Url($url) : $url;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(?string $database): void
    {
        $this->database = $database;
    }

    public function getClusterTag(): string
    {
        return $this->clusterTag;
    }

    public function setClusterTag(string $clusterTag): void
    {
        $this->clusterTag = $clusterTag;
    }

    public function getServerRole(): ServerNodeRole
    {
        return $this->serverRole;
    }

    public function setServerRole(ServerNodeRole $serverRole): void
    {
        $this->serverRole = $serverRole;
    }

    public function equals(?object $o): bool
    {
        if ($this == $o) return true;
        if ($o == null || (get_class($this) != get_class($o))) return false;

        /** @var ServerNode $that */
        $that = $o;

        if ($this->url != null ? !$this->url->getValue() == $that->url->getValue() : $that->url != null) return false;
        return $this->database != null ? $this->database == $that->database : $that->database == null;
    }

     public function hashCode(): int
     {
        $result = $this->url != null ? StringUtils::hashCode($this->url->getValue()) : 0;
        $result = 31 * $result + ($this->database != null ? StringUtils::hashCode($this->database) : 0);
        return $result;
    }

    private int $lastServerVersionCheck = 0;

    private string $lastServerVersion = '';

    public function getLastServerVersion(): string
    {
        return $this->lastServerVersion;
    }

    public function shouldUpdateServerVersion(): bool
    {
        if (empty($this->lastServerVersion) || $this->lastServerVersionCheck > 100) {
            return true;
        }

        $this->lastServerVersionCheck++;
        return false;
    }

    public function updateServerVersion(string $serverVersion): void
    {
        $this->lastServerVersion = $serverVersion;
        $this->lastServerVersionCheck = 0;

        $this->supportsAtomicClusterWrites = false;

        if ($serverVersion != null) {
            // @todo: Marcin check following line of code
            $tokens = str_split("\\.", $serverVersion);
            try {
                $major = intval($tokens[0]);
                $minor = intval($tokens[1]);

                if ($major > 5 || ($major == 5 && $minor >= 2)) {
                    $this->supportsAtomicClusterWrites = true;
                }
            } catch (NumberFormatException $ignore) {
            }
        }
    }

    public function discardServerVersion(): void
    {
        $this->lastServerVersion = '';
        $this->lastServerVersionCheck = 0;
    }

    public static function createFrom(?ClusterTopology $topology = null): ServerNodeList
    {
        $nodes = new ServerNodeList();
        if ($topology == null) {
            return $nodes;
        }

        foreach ($topology->getMembers() as $key => $value) {
            $serverNode = new ServerNode();
            $serverNode->setUrl($value);
            $serverNode->setClusterTag($key);
            $nodes->append($serverNode);
        }

        foreach ($topology->getWatchers() as $key => $value) {
            $serverNode = new ServerNode();
            $serverNode->setUrl($value);
            $serverNode->setClusterTag($key);
            $nodes->append($serverNode);
        }

        return $nodes;
    }

    private bool $supportsAtomicClusterWrites = false;

    public function isSupportsAtomicClusterWrites(): bool
    {
        return $this->supportsAtomicClusterWrites;
    }

    public function setSupportsAtomicClusterWrites(bool $supportsAtomicClusterWrites): void
    {
        $this->supportsAtomicClusterWrites = $supportsAtomicClusterWrites;
    }
}
