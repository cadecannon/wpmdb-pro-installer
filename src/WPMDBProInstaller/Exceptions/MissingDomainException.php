<?php

namespace CadeCannon\WPMDBProInstaller\Exceptions;

/**
 * Exception thrown if the site domain is not available in the environment
 */
class MissingDomainException extends \Exception
{
    public function __construct($message = '', $code = 0, \Exception $previous = null) {
        parent::__construct(
            'Could not find a domain for WPMDB PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $code,
            $previous
        );
    }
}
