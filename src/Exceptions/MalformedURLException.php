<?php

namespace RavenDB\Exceptions;

class MalformedURLException extends RavenException
{
    public const MESSAGE = 'Malformed URL';

    public function __construct(?string $url = null)
    {
        $message = self::MESSAGE;
        if (!empty($url)) {
            $message .= ': ' . $url;
        }
        parent::__construct($message);
    }
}
