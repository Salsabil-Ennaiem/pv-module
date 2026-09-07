<?php

namespace SalsabilEnnaiem\PvModule\Exceptions;

use RuntimeException;

class PvModuleException extends RuntimeException
{
    public static function create(string $message): self
    {
        return new self($message);
    }
}