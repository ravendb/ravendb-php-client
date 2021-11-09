<?php

namespace RavenDB\Json;

use RavenDB\Documents\Session\DocumentInfo;

// @todo: implement this class
class JsonOperation
{

    public static function entityChanged(array $newObject, DocumentInfo $documentInfo, array $changes): bool
    {
        // @todo: implement this method
//        List<DocumentsChanges> docChanges = changes != null ? new ArrayList<>() : null;
//
//        if (!documentInfo.isNewDocument() && documentInfo.getDocument() != null) {
//            return compareJson("", documentInfo.getId(), documentInfo.getDocument(), newObj, changes, docChanges);
//        }
//
//        if (changes == null) {
//            return true;
//        }
//
//        newChange(null,null, null, null, docChanges, DocumentsChanges.ChangeType.DOCUMENT_ADDED);
//        changes.put(documentInfo.getId(), docChanges);
//        return true;

        return false;
    }
}
