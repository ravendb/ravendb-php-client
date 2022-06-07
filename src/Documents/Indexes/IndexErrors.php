<?php

namespace RavenDB\Documents\Indexes;

use Symfony\Component\Serializer\Annotation\SerializedName;

class IndexErrors
{
    /** @SerializedName ("Name") */
    private ?string $name = null;

    /** @SerializedName ("Errors") */
    private ?IndexingErrorArray $errors = null;

    public function __construct()
    {
        $this->errors = new IndexingErrorArray();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getErrors(): ?IndexingErrorArray
    {
        return $this->errors;
    }

    public function setErrors(?IndexingErrorArray $errors): void
    {
        $this->errors = $errors;
    }
}
