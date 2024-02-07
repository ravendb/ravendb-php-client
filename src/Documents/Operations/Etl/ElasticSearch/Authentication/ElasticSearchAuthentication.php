<?php

namespace RavenDB\Documents\Operations\Etl\ElasticSearch\Authentication;

class ElasticSearchAuthentication
{
    private ?ElasticSearchApiKeyAuthentication $apiKey = null;
    private ?ElasticSearchBasicAuthentication $basic = null;
    private ?ElasticSearchCertificateAuthentication $certificate = null;

    public function getApiKey(): ?ElasticSearchApiKeyAuthentication
    {
        return $this->apiKey;
    }

    public function setApiKey(?ElasticSearchApiKeyAuthentication $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getBasic(): ?ElasticSearchBasicAuthentication
    {
        return $this->basic;
    }

    public function setBasic(?ElasticSearchBasicAuthentication $basic): void
    {
        $this->basic = $basic;
    }

    public function getCertificate(): ?ElasticSearchCertificateAuthentication
    {
        return $this->certificate;
    }

    public function setCertificate(?ElasticSearchCertificateAuthentication $certificate): void
    {
        $this->certificate = $certificate;
    }
}
