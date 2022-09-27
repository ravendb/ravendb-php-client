<?php

namespace tests\RavenDB;

use DateTime;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Indexes\IndexErrorsArray;
use RavenDB\Documents\Operations\Indexes\GetIndexErrorsOperation;
use RavenDB\Utils\DateUtils;

// !status: DONE
class RavenTestHelper
{
    public static function utcToday(): DateTime
    {
        $today = DateUtils::now();
        $today->setTime(0,0);
        return $today;
    }

    public static function assertNoIndexErrors(?DocumentStoreInterface $store, ?string $databaseName = null): void
    {
        /** @var IndexErrorsArray $errors */
        $errors = $store->maintenance()->forDatabase($databaseName)->send(new GetIndexErrorsOperation());

        $errorMessage = null;

        /** @ var IndexErrors $error */
        foreach ($errors as $indexErrors) {
            if ($indexErrors == null || $indexErrors->getErrors() == null || count($indexErrors->getErrors()) == 0) {
                continue;
            }

            $errorMessage .= "Index Errors for '"
                    . $indexErrors->getName()
                    . "' ("
                    . count($indexErrors->getErrors())
                    . ")";
            $errorMessage .= PHP_EOL;

            foreach ($indexErrors->getErrors() as $indexError) {
                $errorMessage .= "- " . $indexError;
                $errorMessage .= PHP_EOL;
            }

            $errorMessage .= PHP_EOL;
        }

        if (empty($errorMessage)) {
            return;
        }

        throw new IllegalStateException($errorMessage);
    }
}
