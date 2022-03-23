<?php

namespace RavenDB\Primitives;

use Exception;

class ExceptionsUtils
{
    public static function accept($action):  object
    {
        try {
            return $action->get();
        } catch (\Throwable $exception) {
            throw self::unwrapException($exception);
        }
    }

    public static function unwrapException(\Throwable $exception): \Throwable
    {
//        if ($exception instanceof ExecutionException) {
//            /** @var ExecutionException $computationException */
//            $computationException = $exception;
//            return self::unwrapException($computationException->getCause());
//        }

        if (is_a($exception, \RuntimeException::class)) {
            return $exception;
        }

        return new Exception($exception->getMessage(), $exception->getCode(), $exception);
    }
}
