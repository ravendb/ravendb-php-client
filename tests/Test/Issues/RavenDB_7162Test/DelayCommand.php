<?php

namespace tests\RavenDB\Test\Issues\RavenDB_7162Test;

use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\ServerNode;
use RavenDB\Http\VoidRavenCommand;
use RavenDB\Type\Duration;
use tests\RavenDB\Infrastructure\TestRunGuard;

class DelayCommand extends VoidRavenCommand
{
    private ?Duration $value = null;

    public function __construct(?Duration $value)
    {
        parent::__construct();
        $this->value = $value;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $url = $serverNode->getUrl();

        $url .= TestRunGuard::isServerVersion52()
            ? "/test/delay?value="
            : "/admin/test/delay?value=";

        $url .= $this->value->toMillis();

        return $url;
    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode));
    }
}
