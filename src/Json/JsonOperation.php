<?php

namespace RavenDB\Json;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Session\ChangeType;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\DocumentsChanges;
use RavenDB\Documents\Session\DocumentsChangesArray;
use RavenDB\Exceptions\IllegalArgumentException;

class JsonOperation
{
    /**
     * @deprecated
     *
     * @throws IllegalArgumentException
     */
    public static function entityChanged(
        array $newObject,
        DocumentInfo $documentInfo,
        array &$changes
    ): bool {
        if ($changes == null) {
            return self::isEntityChanged($newObject, $documentInfo);
        }

        $docChanges = self::getEntityChanges($newObject, $documentInfo);

        $changes[$documentInfo->getId()] = $docChanges;

        return self::isEntityChanged($newObject, $documentInfo);
    }

    /**
     * @throws IllegalArgumentException
     */
    public static function isEntityChanged(
        array $newObject,
        DocumentInfo $documentInfo
    ): bool {
        if ($documentInfo->isNewDocument() || ($documentInfo->getDocument() == null)) {
            return true;
        }

        return self::isJsonDifferent(
            $documentInfo->getDocument(),
            $newObject
        );
    }

    /**
     * @throws IllegalArgumentException
     */
    public static function getEntityChanges(
        array $newObject,
        DocumentInfo $documentInfo
    ): DocumentsChangesArray {

        if (!$documentInfo->isNewDocument() && $documentInfo->getDocument() != null) {
            return self::extractChangesFromJson(
                "",
                $documentInfo->getDocument(),
                $newObject,
            );
        }

        $docChanges = new DocumentsChangesArray();
        $docChanges->append(DocumentsChanges::new(ChangeType::documentAdded()));

        return $docChanges;
    }

    /**
     * @throws IllegalArgumentException
     */
    private static function isJsonDifferent(
        array $originalJson,
        array $newJson
    ): bool {
        $newJsonProps = array_keys($newJson);
        $oldJsonProps = array_keys($originalJson);

        $newFields = array_diff($newJsonProps, $oldJsonProps);
        $removedFields = array_diff($oldJsonProps, $newJsonProps);

        if (count($removedFields)) {
            return true;
        }

        foreach ($newJsonProps as $prop) {
            if ($prop === DocumentsMetadata::LAST_MODIFIED ||
                $prop === DocumentsMetadata::COLLECTION ||
                $prop === DocumentsMetadata::CHANGE_VECTOR ||
                $prop === DocumentsMetadata::ID) {
                continue;
            }

            if (in_array($prop, $newFields)) {
                return true;
            }

            $newProp = $newJson[$prop];
            $oldProp = $originalJson[$prop];

            switch (gettype($newProp)) {
                case "integer":
                case "double":
                case "boolean":
                case "string":
                    if ($newProp === $oldProp || self::compareValues($oldProp, $newProp)) {
                        break;
                    }
                    return true;
                case "NULL":
                    if ($oldProp != null) {
                        return true;
                    }
                    break;
                case "array":
                    if (!is_array($oldProp)) {
                        return true;
                    }
                    if (self::isJsonDifferent($oldProp, $newProp)) {
                        return true;
                    }
                    break;
                default:
                    throw new IllegalArgumentException();
            }
        }

        return false;
    }



    /**
     * @throws IllegalArgumentException
     */
    private static function extractChangesFromJson(
        string $fieldPath,
        array $originalJson,
        array $newJson
    ): DocumentsChangesArray {
        $docChanges = new DocumentsChangesArray();

        $newJsonProps = array_keys($newJson);
        $oldJsonProps = array_keys($originalJson);

        $newFields = array_diff($newJsonProps, $oldJsonProps);
        $removedFields = array_diff($oldJsonProps, $newJsonProps);

        foreach ($removedFields as $field) {
            $docChanges->append(
                DocumentsChanges::new(
                    ChangeType::removedField(),
                    $fieldPath,
                    $field
                )
            );
        }

        foreach ($newJsonProps as $prop) {
            if ($prop === DocumentsMetadata::LAST_MODIFIED ||
                $prop === DocumentsMetadata::COLLECTION ||
                $prop === DocumentsMetadata::CHANGE_VECTOR ||
                $prop === DocumentsMetadata::ID) {
                continue;
            }

            if (in_array($prop, $newFields)) {
                $docChanges->append(
                    DocumentsChanges::new(
                        ChangeType::newField(),
                        $fieldPath,
                        $prop,
                        $newJson[$prop]
                    )
                );
                continue;
            }

            $newValue = $newJson[$prop];
            $oldValue = $originalJson[$prop];

            switch (gettype($newValue)) {
                case "integer":
                case "double":
                case "boolean":
                case "string":
                    if ($newValue === $oldValue || self::compareValues($oldValue, $newValue)) {
                        break;
                    }
                        $docChanges->append(
                            DocumentsChanges::new(
                                ChangeType::fieldChanged(),
                                $fieldPath,
                                $prop,
                                $newValue,
                                $oldValue
                            )
                        );
                    break;
                case "NULL":
                    if ($oldValue == null) {
                        break;
                    }
                    $docChanges->append(
                        DocumentsChanges::new(
                            ChangeType::fieldChanged(),
                            $fieldPath,
                            $prop,
                            null,
                            $oldValue
                        )
                    );
                    break;

                case "array":
                    $newValueIsArray = JsonArray::isArray($newValue);
                    $oldValueIsArray = JsonArray::isArray($oldValue);

                    if ($newValueIsArray != $oldValueIsArray) {
                        $docChanges->append(
                            DocumentsChanges::new(
                                ChangeType::fieldChanged(),
                                $fieldPath,
                                $prop,
                                $newValue,
                                $oldValue
                            )
                        );
                        break;
                    }

                    $dcs = null;
                    if ($newValueIsArray && $oldValueIsArray) {
                        $dcs = self::extractJsonArrayChanges(
                            self::fieldPathCombine($fieldPath, (string)$prop),
                            $oldValue,
                            $newValue,
                            (string)$prop
                        );
                    } else {
                        if ($oldValue == null) {
                            $docChanges->append(
                                DocumentsChanges::new(
                                    ChangeType::fieldChanged(),
                                    $fieldPath,
                                    $prop,
                                    $newValue,
                                    null
                                )
                            );
                        } else {
                            $dcs = self::extractChangesFromJson(
                                self::fieldPathCombine($fieldPath, (string)$prop),
                                $oldValue,
                                $newValue
                            );
                        }

                    }
                    if ($dcs !== null) {
                        foreach ($dcs as $dc) {
                            $docChanges->append($dc);
                        }
                    }
                    break;
                default:
                    throw new IllegalArgumentException();
            }
        }

        return $docChanges;
    }

    /**
     * @throws IllegalArgumentException
     */
    private static function extractJsonArrayChanges(
        string $fieldPath,
        array $oldArray,
        array $newArray,
        string $propName
    ): DocumentsChangesArray {
        $docChanges = new DocumentsChangesArray();

        $intersectKeys = array_intersect_key($oldArray, $newArray);

        foreach ($intersectKeys as $propName => $value) {
            $oldArrayItem = $oldArray[$propName];
            $newArrayItem = $newArray[$propName];

            $oldArrayItemIsArray = JsonArray::isArray($oldArrayItem);
            $newArrayItemIsArray = JsonArray::isArray($newArrayItem);

            if ($oldArrayItemIsArray) {
                if ($newArrayItemIsArray) {
                    $dcs = self::extractJsonArrayChanges(
                            self::addIndexFieldPath($fieldPath, $propName),
                            $oldArrayItem,
                            $newArrayItem,
                            $propName
                        );
                    foreach ($dcs as $c) {
                        $docChanges->append($c);
                    }
                } else {
                    $docChanges->append(
                        DocumentsChanges::new(
                            ChangeType::arrayValueChanged(),
                            self::addIndexFieldPath($fieldPath, $propName),
                            $propName,
                            $newArrayItem,
                            $oldArrayItem
                        )
                    );
                }
            } elseif ($oldArrayItem == null) {
                if ($newArrayItem != null) {
                    $docChanges->append(
                        DocumentsChanges::new(
                            ChangeType::arrayValueAdded(),
                            self::addIndexFieldPath($fieldPath, $propName),
                            $propName,
                            $newArrayItem,
                            $oldArrayItem
                        )
                    );
                }
            } else {
                if ((gettype($oldArrayItem) !== gettype($newArrayItem)) || !is_array($oldArrayItem) || !is_array($newArrayItem)) {
                    if ($oldArrayItem !== $newArrayItem) {
                        $docChanges->append(
                            DocumentsChanges::new(
                                ChangeType::arrayValueChanged(),
                                self::addIndexFieldPath($fieldPath, $propName),
                                $propName,
                                $newArrayItem,
                                $oldArrayItem
                            )
                        );
                    }
                } else {
                    $dcs = self::extractChangesFromJson(
                        self::addIndexFieldPath($fieldPath, $propName),
                        $oldArrayItem,
                        $newArrayItem
                    );
                    foreach ($dcs as $c) {
                        $docChanges->append($c);
                    }
                }
            }
        }

        $removedFieldsKeys = array_diff_key($oldArray, $newArray);

        foreach ($removedFieldsKeys as $propName => $oldArrayItem) {
            $docChanges->append(
                DocumentsChanges::new(
                    ChangeType::arrayValueRemoved(),
                    $fieldPath,
                    $propName,
                    null,
                    $oldArrayItem
                )
            );
        }

        $newFieldsKeys = array_diff_key($newArray, $oldArray);
        foreach ($newFieldsKeys as $propName => $newArrayItem) {
            $docChanges->append(
                DocumentsChanges::new(
                    ChangeType::arrayValueAdded(),
                    $fieldPath,
                    $propName,
                    $newArrayItem,
                    null
                )
            );
        }

        return $docChanges;
    }

    // @todo: Check if this is right
    private static function compareValues($oldProp, $newProp): bool
    {
        if (is_numeric($oldProp) || is_numeric($newProp)) {
            return $oldProp == $newProp;
        } else {
            return $oldProp === $newProp;
        }
    }

    /**
     * @param string $fieldPath
     * @param string|int $position
     * @return string
     */
    private static function addIndexFieldPath(string $fieldPath, $position): string
    {
        return $fieldPath . "[" . $position . "]";
    }

    private static function fieldPathCombine(string $path1, string $path2): string
    {
        return empty($path1) ? $path2 : $path1 . "." . $path2;
    }
}
