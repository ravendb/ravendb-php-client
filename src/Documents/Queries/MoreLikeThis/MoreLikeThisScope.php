<?php

namespace RavenDB\Documents\Queries\MoreLikeThis;

use Closure;
use RavenDB\Documents\Session\Tokens\MoreLikeThisToken;
use RavenDB\Extensions\JsonExtensions;

class MoreLikeThisScope
{
    private ?MoreLikeThisToken $token = null;
    private ?Closure $addQueryParameter = null;
    private ?Closure $onDispose = null;

    public function __construct(?MoreLikeThisToken $token, Closure $addQueryParameter, ?Closure $onDispose = null)
    {
        $this->token = $token;
        $this->addQueryParameter = $addQueryParameter;
        $this->onDispose = $onDispose;
    }


    public function close(): void
    {
        if ($this->onDispose != null) {
            $onDispose = $this->onDispose;
            $onDispose();
        }
    }

    public function withOptions(?MoreLikeThisOptions $options): void
    {
        if ($options == null) {
            return;
        }

        // force using *non* entity serializer here:
        $optionsAsJson = JsonExtensions::getDefaultMapper()->normalize($options);
        $addQueryParameter = $this->addQueryParameter;
        $this->token->optionsParameterName = $addQueryParameter($optionsAsJson);
    }

    public function withDocument(?string $document): void
    {
        $addQueryParameter = $this->addQueryParameter;
        $this->token->documentParameterName = $addQueryParameter($document);
    }
}
