<?php

namespace RavenDB\Documents\Operations\Configuration;

use RavenDB\Http\ReadBalanceBehavior;
use RavenDB\Http\LoadBalanceBehavior;
use RavenDB\Exceptions\IllegalArgumentException;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ClientConfiguration
{
    /** @SerializedName ("IdentityPartsSeparator") */
    private ?string $identityPartsSeparator = null;
    /** @SerializedName ("Etag") */
    private ?int $etag = null;
    /** @SerializedName ("Disabled") */
    private bool $disabled = false;
    /** @SerializedName ("MaxNumberOfRequestsPerSession") */
    private ?int $maxNumberOfRequestsPerSession = null;
    /** @SerializedName ("ReadBalanceBehavior") */
    private ?ReadBalanceBehavior $readBalanceBehavior = null;
    /** @SerializedName ("LoadBalanceBehavior") */
    private ?LoadBalanceBehavior $loadBalanceBehavior = null;
    /** @SerializedName ("LoadBalancerContextSeed") */
    private ?int $loadBalancerContextSeed = null;

    public function getIdentityPartsSeparator(): ?string
    {
        return $this->identityPartsSeparator;
    }

    public function setIdentityPartsSeparator(?string $identityPartsSeparator): void
    {
        if ('|' == $identityPartsSeparator) {
            throw new IllegalArgumentException("Cannot set identity parts separator to '|'");
        }
        if ($identityPartsSeparator != null && strlen($identityPartsSeparator) > 1) {
            throw new IllegalArgumentException("Cannot set identity parts separator to string longer then 1");
        }
        $this->identityPartsSeparator = $identityPartsSeparator;
    }

    public function getEtag(): ?int
    {
        return $this->etag;
    }

    public function setEtag(?int $etag): void
    {
        $this->etag = $etag;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): void
    {
        $this->disabled = $disabled;
    }

    public function getMaxNumberOfRequestsPerSession(): ?int
    {
        return $this->maxNumberOfRequestsPerSession;
    }

    public function setMaxNumberOfRequestsPerSession(?int $maxNumberOfRequestsPerSession): void
    {
        $this->maxNumberOfRequestsPerSession = $maxNumberOfRequestsPerSession;
    }

    public function getReadBalanceBehavior(): ?ReadBalanceBehavior
    {
        return $this->readBalanceBehavior;
    }

    public function setReadBalanceBehavior(?ReadBalanceBehavior $readBalanceBehavior): void
    {
        $this->readBalanceBehavior = $readBalanceBehavior;
    }

    public function getLoadBalanceBehavior(): ?LoadBalanceBehavior
    {
        return $this->loadBalanceBehavior;
    }

    public function setLoadBalanceBehavior(?LoadBalanceBehavior $loadBalanceBehavior): void
    {
        $this->loadBalanceBehavior = $loadBalanceBehavior;
    }

    public function getLoadBalancerContextSeed(): ?int
    {
        return $this->loadBalancerContextSeed;
    }

    public function setLoadBalancerContextSeed(?int $loadBalancerContextSeed): void
    {
        $this->loadBalancerContextSeed = $loadBalancerContextSeed;
    }
}
