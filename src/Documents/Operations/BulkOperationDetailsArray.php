<?php

namespace RavenDB\Documents\Operations;

use _PHPStan_b8e553790\Nette\Neon\Exception;
use RavenDB\Type\TypedArray;

class BulkOperationDetailsArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(BulkOperationDetailsInterface::class);
    }

    /**
     * @throws Exception
     */
    public static function createNewItemObjectFromValue(mixed $value): object
    {
        if (!array_key_exists('$type', $value)) {
            throw new Exception('Details array should have \'\$type\' key inside.');
        }

        $type = $value['$type'];

//        print_r($type);

        switch ($type) {
            case 'Raven.Client.Documents.Operations.BulkOperationResult+DeleteDetails, Raven.Client':
                return BulkOperationResultDeleteDetails::fromArray($value);
            case 'Raven.Client.Documents.Operations.BulkOperationResult+PatchDetails, Raven.Client':
                return BulkOperationResultPatchDetails::fromArray($value);
            default:

                break;
        }

        throw new Exception('Unknown type: ' . $type);

    }
}
