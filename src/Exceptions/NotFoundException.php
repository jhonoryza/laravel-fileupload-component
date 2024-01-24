<?php

namespace Jhonoryza\Component\FileUpload\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public static function uuidAlreadyExists(): self
    {
        return new static('The given uuid is being used for an existing media item.');
    }
}
