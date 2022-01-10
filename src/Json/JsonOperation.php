<?php

namespace RavenDB\Json;

use RavenDB\Constants\Metadata;
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

        if ($documentInfo->isNewDocument() && $documentInfo->getDocument() != null) {
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
            if ($prop === Metadata::LAST_MODIFIED ||
                $prop === Metadata::COLLECTION ||
                $prop === Metadata::CHANGE_VECTOR ||
                $prop === Metadata::ID) {
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
            if ($prop === Metadata::LAST_MODIFIED ||
                $prop === Metadata::COLLECTION ||
                $prop === Metadata::CHANGE_VECTOR ||
                $prop === Metadata::ID) {
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
                    if (!is_array($oldValue)) {
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
                        $docChanges = array_merge(
                            $docChanges,
                            self::extractChangesFromJson(
                                self::fieldPathCombine($fieldPath, (string)$prop),
                                $oldValue,
                                $newValue
                            )
                        );
                    break;
                default:
                    throw new IllegalArgumentException();
            }
        }

        return $docChanges;
    }

    // @todo: Check do we need this part of code or we should delete it
//    /**
//     * @throws IllegalArgumentException
//     */
//    private static function compareJsonArray(
//        string $fieldPath,
//        string $id,
//        array $oldArray,
//        array $newArray,
//        ?array &$changes,
//        ?DocumentsChangesArray $docChanges,
//        string $propName
//    ): bool {
//
//        if (count($oldArray) != count($newArray) && $changes == null) {
//            return true;
//        }
//
//        $position = 0;
//        $changed = false;
//
//        $oldArrayItem = $oldArray[$position];
//        $newArrayItem = $newArray[$position];
//
//        while ($position < count($oldArray) && $position < count($newArray)) {
//            if (is_array($oldArrayItem)) {
//                if (is_array($newArrayItem)) {
//                    $changed = $changed || self::compareJsonArray(
//                        self::addIndexFieldPath($fieldPath, $position),
//                        $id,
//                        $oldArrayItem,
//                        $newArrayItem,
//                        $changes,
//                        $docChanges,
//                        $propName
//                    );
//                } else {
//                    $changed = true;
//                    if ($changes) {
//                        self::newChange(
//                            self::addIndexFieldPath($fieldPath, $position),
//                            $propName,
//                            $newArrayItem,
//                            $oldArrayItem,
//                            $docChanges,
//                            ChangeType::arrayValueChanged()
//                        );
//                    }
//                }
//            } elseif ($oldArrayItem == null) {
//                if ($newArrayItem != null) {
//                    $changed = true;
//                    if ($changes) {
//                        self::newChange(
//                            self::addIndexFieldPath($fieldPath, $position),
//                            $propName,
//                            $newArrayItem,
//                            $oldArrayItem,
//                            $docChanges,
//                            ChangeType::arrayValueAdded()
//                        );
//                    }
//                }
//            } else {
//                if ($oldArrayItem !== $newArrayItem) {
//                    if ($changes) {
//                        self::newChange(
//                            self::addIndexFieldPath($fieldPath, $position),
//                            $propName,
//                            $newArrayItem,
//                            $oldArrayItem,
//                            $docChanges,
//                            ChangeType::arrayValueChanged()
//                        );
//                    }
//                    $changed = true;
//                }
//            }
//
//            $position++;
//            $oldArrayItem = $oldArray[$position];
//            $newArrayItem = $newArray[$position];
//        }
//
//        if ($changes == null) {
//            return $changed;
//        }
//
//        while ($position < count($oldArray)) {
//            self::newChange(
//                $fieldPath,
//                $propName,
//                null,
//                $oldArray[$position],
//                $docChanges,
//                ChangeType::arrayValueRemoved()
//            );
//            $position++;
//        }
//
//        while ($position < count($newArray)) {
//            self::newChange(
//                $fieldPath,
//                $propName,
//                $newArray[$position],
//                null,
//                $docChanges,
//                ChangeType::arrayValueAdded()
//            );
//            $position++;
//        }
//
//        return $changed;
//    }

    // @todo: Check if this is right
    private static function compareValues($oldProp, $newProp): bool
    {
        if (is_numeric($oldProp) || is_numeric($newProp)) {
            return $oldProp == $newProp;
        } else {
            return $oldProp === $newProp;
        }
    }

    private static function addIndexFieldPath(string $fieldPath, int $position): string
    {
        return $fieldPath . "[" . $position . "]";
    }

    private static function fieldPathCombine(string $path1, string $path2): string
    {
        return empty($path1) ? $path2 : $path1 . "." . $path2;
    }
}
