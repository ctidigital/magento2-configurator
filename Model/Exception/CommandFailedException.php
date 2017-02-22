<?php

namespace CtiDigital\Configurator\Model\Exception;

use Exception;

/**
 * This exception indicates that the CLI command requested by the user cannot be finished due to an error.
 */
class CommandFailedException extends \RuntimeException
{
    const GENERAL_ERROR = 1;

    public function __construct($message = '', $code = self::GENERAL_ERROR, Exception $previous = null)
    {
        if (!(0 < $code && $code < 256)) {
            throw new \InvalidArgumentException(sprintf('Code must be between 1 and 255, but "%d" given.', $code));
        }

        parent::__construct($message, $code, $previous);
    }

}
