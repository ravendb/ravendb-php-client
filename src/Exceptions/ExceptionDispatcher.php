<?php

namespace RavenDB\Exceptions;

use RavenDB\Constants\HttpStatusCode;
use RavenDB\Exceptions\Documents\Compilation\IndexCompilationException;
use RavenDB\Exceptions\Documents\DocumentConflictException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Http\HttpResponse;
use Throwable;

class ExceptionDispatcher
{
    public static function get(ExceptionSchema $schema, int $code, ?Throwable $inner = null): RavenException
    {
        $message = $schema->getMessage();
        $typeAsString = $schema->getType();

        if ($code == HttpStatusCode::CONFLICT) {
            if (strpos($typeAsString, 'DocumentConflictException') !== false) {
                return DocumentConflictException::fromMessage($message);
            }

            return new ConcurrencyException($message);
        }

        $error = $schema->getError() . PHP_EOL . "The server at " . $schema->getUrl() . " responded with status code: " . $code;

        $type = self::getType($typeAsString);
        if ($type == null) {
            return new RavenException($error, $inner);
        }

        try {
            $exception = new $type($error);
        } catch (Throwable $e) {
            return new RavenException($error, $inner);
        }

        if (!is_a($type, RavenException::class, true)) {
            return new RavenException($error, $exception);
        }

        return $exception;
    }

    public static function throwException(?HttpResponse $response = null): void
    {
        if ($response == null) {
            throw new IllegalArgumentException('Response cannot be null.');
        }

        try {
            $jsonText = $response->getContent();
            $json = JsonExtensions::getDefaultMapper()->decode($jsonText, 'json');
            /** @var ExceptionSchema $schema */
            $schema = JsonExtensions::getDefaultMapper()->deserialize($jsonText, ExceptionSchema::class, 'json');

            if ($response->getStatusCode() == HttpStatusCode::CONFLICT) {
                self::throwConflict($schema, $json);
            }

            $type = self::getType($schema->getType());
            if ($type == null) {
                throw RavenException::generic($schema->getError(), $jsonText);
            }

            $exception = new RavenException();

            try {
                $exception = new $type($schema->getError());
            } catch (Throwable $e) {
                throw RavenException::generic($schema->getError(), $jsonText);
            }
            if (!($exception instanceof RavenException)) {
                throw new RavenException($schema->getError(), $exception);
            }

            if ($exception instanceof IndexCompilationException) {
                /** @var IndexCompilationException $indexCompilationException */
                $indexCompilationException = $exception;
                $indexDefinitionProperty = array_key_exists('TransformerDefinitionProperty', $json) ?  $json['TransformerDefinitionProperty'] : null;
                if ($indexDefinitionProperty != null) {
                    $indexCompilationException->setIndexDefinitionProperty($indexDefinitionProperty);
                }

                $problematicText = array_key_exists('ProblematicText', $json) ?  $json['ProblematicText'] : null;
                if ($problematicText != null) {
                    $indexCompilationException->setProblematicText($problematicText);
                }

                throw $indexCompilationException;
            }

            throw $exception;

        } catch (Throwable $exception) {
            if ($exception instanceof RavenException) {
                throw $exception;
            }

            throw new RavenException($exception->getMessage(), $exception);
        }
    }


    /**
     * @throws BadResponseException
     * @throws ConcurrencyException
     * @throws DocumentConflictException
     */
    private static function throwConflict(ExceptionSchema $schema, ?array $json): void
    {
        if (str_contains($schema->getType(), 'DocumentConflictException')) {
            throw DocumentConflictException::fromJson($json);
        }

        if (str_contains($schema->getType(),'ClusterTransactionConcurrencyException')) {
            $ctxConcurrencyException = new ClusterTransactionConcurrencyException($schema->getMessage());

            $idNode = array_key_exists('Id', $json) ? strval($json['Id']) : null;
            if ($idNode != null && !empty($idNode)) {
                $ctxConcurrencyException->setId($idNode);
            }

            $expectedChangeVectorNode = array_key_exists('ExpectedChangeVector', $json) ? strval($json['ExpectedChangeVector']) : null;
            if ($expectedChangeVectorNode != null && !empty($expectedChangeVectorNode)) {
                $ctxConcurrencyException->setExpectedChangeVector($expectedChangeVectorNode);
            }

            $actualChangeVectorNode = array_key_exists('ActualChangeVector', $json) ? strval($json['ActualChangeVector']) : null;
            if ($actualChangeVectorNode != null && !empty($actualChangeVectorNode)) {
                $ctxConcurrencyException->setActualChangeVector($actualChangeVectorNode);
            }

            $concurrencyViolationsNode = array_key_exists('ConcurrencyViolations', $json) ? $json['ConcurrencyViolations'] : null;
            if ($concurrencyViolationsNode == null || !is_array($concurrencyViolationsNode)) {
                throw $ctxConcurrencyException;
            }

            $concurrencyViolationsJsonArray = $concurrencyViolationsNode;

            $violationArray = new ConcurrencyViolationArray();

            foreach ($concurrencyViolationsJsonArray as $violation) {
                if ($violation == null) {
                    continue;
                }

                $current = new ConcurrencyViolation();

                $jsonId = array_key_exists('Id', $violation) ? strval($violation["Id"]) : null;
                if ($jsonId != null ) {
                    $current->setId($jsonId);
                }

                $typeText = strval($violation["Type"]);
                switch ($typeText) {
                    case "Document" :
                        $current->setType(ViolationOnType::document());
                        break;
                    case "CompareExchange":
                        $current->setType(ViolationOnType::compareExchange());
                        break;
                    default:
                        throw new IllegalArgumentException("Invalid type: " . $typeText);
                }

                $jsonExpected = array_key_exists('Expected', $violation) ? intval($violation['Expected']) : null;
                if ($jsonExpected != null) {
                    $current->setExpected($jsonExpected);
                }

                $jsonActual = array_key_exists('Actual', $violation) ? intval($violation['Actual']) : null;
                if ($jsonActual != null) {
                    $current->setActual($jsonActual);
                }

                $violationArray[] = $current;
            }

            $ctxConcurrencyException->setConcurrencyViolations($violationArray);

            throw $ctxConcurrencyException;
        }

        $concurrencyException = new ConcurrencyException($schema->getMessage());
        $idNode = array_key_exists('Id', $json) ? strval($json["Id"]) : null;
        if ($idNode != null) {
            $concurrencyException->setId($idNode);
        }

        $expectedChangeVectorNode = array_key_exists('ExpectedChangeVector', $json) ? strval($json['ExpectedChangeVector']) : null;
        if ($expectedChangeVectorNode != null) {
            $concurrencyException->setExpectedChangeVector($expectedChangeVectorNode);
        }

        $actualChangeVectorNode = array_key_exists('ActualChangeVector', $json) ? strval($json['ActualChangeVector']) : null;
        if ($actualChangeVectorNode != null) {
            $concurrencyException->setActualChangeVector($actualChangeVectorNode);
        }

        throw $concurrencyException;
    }

    private static function getType(?string $typeAsString): ?string
    {
        if ($typeAsString == "System.TimeoutException") {
            return TimeoutException::class;
        }

        if ($typeAsString == "System.ArgumentNullException") {
            return IllegalArgumentException::class;
        }

        $prefix = "Raven.Client.Exceptions.";

        if (str_starts_with($typeAsString, $prefix)) {
            $exceptionName = substr($typeAsString, strlen($prefix));

            if (strpos($exceptionName, '.') != false) {
                $tokens = preg_split("/\\./", $exceptionName);

//                for( $i = 0; $i<count($tokens); $i++) {
//                    $tokens[$i] = strtolower($tokens[$i]);
//                }
                $exceptionName = join("\\", $tokens);
            }

            try {
                return 'RavenDB\\Exceptions\\' . $exceptionName;
            } catch (Throwable $exception) {
                return null;
            }
        }

        return null;
    }
}
