<?php

namespace RavenDB\Constants;

class PhpClient
{
    // We need to define min and max values for 32bit int
    // We can't use PHP_INT_MAX and PHP_INT_MIN because they can be 64bit values
    public const INT_MAX_VALUE = 2147483647;
    public const INT_MIN_VALUE = -2147483648;
}
