<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Http\ResultInterface;
use RavenDB\Type\StringArray;

use Symfony\Component\Serializer\Annotation\SerializedName;

class CertificateMetadata implements ResultInterface
{
    /** @SerializedName("Name") */
    private ?string $name = null;

    /** @SerializedName("SecurityClearance") */
    private ?SecurityClearance $securityClearance = null;

    /** @SerializedName("Thumbprint") */
    private ?string $thumbprint = null;

    /** @SerializedName("NotAfter") */
    private ?\DateTimeInterface $notAfter = null;

    /** @SerializedName("Permissions") */
    protected ?DatabaseAccessArray $permissions = null;

    /** @SerializedName("CollectionSecondaryKeys") */
    private StringArray $collectionSecondaryKeys;

    /** @SerializedName("CollectionPrimaryKey") */
    private string $collectionPrimaryKey = "";

    /** @SerializedName("PublicKeyPinningHash") */
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

    public function getPermissions(): ?DatabaseAccessArray
    {
        return $this->permissions;
    }

    public function setPermissions(null|array|DatabaseAccessArray $permissions): void
    {
        if (is_array($permissions)) {
            $permissions = DatabaseAccessArray::fromArray($permissions);
        }

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

    /**
     * @param StringArray|array $collectionSecondaryKeys
     */
    public function setCollectionSecondaryKeys($collectionSecondaryKeys): void
    {
        if (is_a($collectionSecondaryKeys, StringArray::class)) {
            $this->collectionSecondaryKeys = $collectionSecondaryKeys;
            return;
        }

        $this->collectionSecondaryKeys = StringArray::fromArray($collectionSecondaryKeys);
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
