<?php

namespace RavenDB\ServerWide\Operations\Configuration;

use RavenDB\Type\StringMap;

class DatabaseSettings
{
    private ?StringMap $settings = null;

    public function getSettings(): ?StringMap
    {
        return $this->settings;
    }

    public function setSettings(?StringMap $settings): void
    {
        $this->settings = $settings;
    }
}
