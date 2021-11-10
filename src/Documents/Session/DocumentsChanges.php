<?php

namespace RavenDB\Documents\Session;

class DocumentsChanges
{
    private ?array $fieldOldValue = null;

    private ?array $fieldNewValue = null;

    private ChangeType $change;

    private ?string $fieldName = null;

    private ?string $fieldPath = null;

    public function __construct(?ChangeType $changeType = null)
    {
        $this->change = $changeType ?? ChangeType::unknown();
    }

    public function getFieldOldValue(): ?array
    {
        return $this->fieldOldValue;
    }

    public function setFieldOldValue(?array $fieldOldValue): void
    {
        $this->fieldOldValue = $fieldOldValue;
    }

    public function getFieldNewValue(): ?array
    {
        return $this->fieldNewValue;
    }

    public function setFieldNewValue(?array $fieldNewValue): void
    {
        $this->fieldNewValue = $fieldNewValue;
    }

    public function getChange(): ChangeType
    {
        return $this->change;
    }

    public function setChange(ChangeType $change): void
    {
        $this->change = $change;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function setFieldName(?string $fieldName): void
    {
        $this->fieldName = $fieldName;
    }

    public function getFieldFullName(): ?string
    {
        return empty($this->fieldPath) ? $this->fieldName : $this->fieldPath . "." . $this->fieldName;
    }

    /**
     * @return string - Path of field on which the change occurred.
     */
    public function getFieldPath(): ?string
    {
        return $this->fieldPath;
    }

    /**
     * @param string|null $fieldPath - Path of field on which the change occurred.
     */
    public function setFieldPath(?string $fieldPath): void
    {
        $this->fieldPath = $fieldPath;
    }

    public static function new(
        ChangeType $changeType,
        ?string $fieldPath = null,
        ?string $name = null,
        ?array $newValue = null,
        ?array $oldValue = null
    ): self {
        $documentsChanges = new DocumentsChanges($changeType);
        $documentsChanges->setFieldName($name);
        $documentsChanges->setFieldNewValue($newValue);
        $documentsChanges->setFieldOldValue($oldValue);
        $documentsChanges->setFieldPath($fieldPath);

        return $documentsChanges;
    }
}
