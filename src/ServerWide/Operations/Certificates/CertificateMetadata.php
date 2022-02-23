<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Type\StringArray;

// !status: DONE
class CertificateMetadata
{
    private ?string $name = null;
    private ?SecurityClearance $securityClearance = null;
    private ?string $thumbprint = null;
    private ?\DateTimeInterface $notAfter = null;
    private DatabaseAccessArray $permissions;
    private StringArray $collectionSecondaryKeys;
    private string $collectionPrimaryKey = "";
    private ?string $publicKeyPinningHash = null;

    public function __construct()
    {
        $this->permissions = new DatabaseAccessArray();
        $this->collectionSecondaryKeys = new StringArray();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getSecurityClearance(): ?SecurityClearance
    {
        return $this->securityClearance;
    }

    public function setSecurityClearance(?SecurityClearance $securityClearance): void
    {
        $this->securityClearance = $securityClearance;
    }

    public function getThumbprint(): ?string
    {
        return $this->thumbprint;
    }

    public function setThumbprint(?string $thumbprint): void
    {
        $this->thumbprint = $thumbprint;
    }

    public function getNotAfter(): ?\DateTimeInterface
    {
        return $this->notAfter;
    }

    public function setNotAfter(?\DateTimeInterface $notAfter): void
    {
        $this->notAfter = $notAfter;
    }

    public function getPermissions(): DatabaseAccessArray
    {
        return $this->permissions;
    }

    public function setPermissions(DatabaseAccessArray $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function addPermission(string $key, DatabaseAccess $permission): void
    {
        $this->permissions->offsetSet($key, $permission);
    }

    public function getCollectionSecondaryKeys(): StringArray
    {
        return $this->collectionSecondaryKeys;
    }

    public function setCollectionSecondaryKeys(StringArray $collectionSecondaryKeys): void
    {
        $this->collectionSecondaryKeys = $collectionSecondaryKeys;
    }

    public function addCollectionSecondaryKey(string $key): void
    {
        $this->collectionSecondaryKeys->append($key);
    }

    public function getCollectionPrimaryKey(): string
    {
        return $this->collectionPrimaryKey;
    }

    public function setCollectionPrimaryKey(string $collectionPrimaryKey): void
    {
        $this->collectionPrimaryKey = $collectionPrimaryKey;
    }

    public function getPublicKeyPinningHash(): ?string
    {
        return $this->publicKeyPinningHash;
    }

    public function setPublicKeyPinningHash(?string $publicKeyPinningHash): void
    {
        $this->publicKeyPinningHash = $publicKeyPinningHash;
    }

}
