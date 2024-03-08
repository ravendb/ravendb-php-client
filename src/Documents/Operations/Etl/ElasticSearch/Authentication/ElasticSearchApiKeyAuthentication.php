<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch\Authentication;

class ElasticSearchApiKeyAuthentication
{
    private ?string $apiKeyId = null;
    private ?string $apiKey = null;

    public function getApiKeyId(): ?string
    {
        return $this->apiKeyId;
    }

    public function setApiKeyId(?string $apiKeyId): void
    {
        $this->apiKeyId = $apiKeyId;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }
}
