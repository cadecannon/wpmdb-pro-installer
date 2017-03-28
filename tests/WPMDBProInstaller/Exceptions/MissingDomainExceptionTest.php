<?php

namespace CadeCannon\WPMDBProInstaller\Test\Exceptions;

use CadeCannon\WPMDBProInstaller\Exceptions\MissingDomainException;

class MissingDomainExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $message = 'FIELD';
        $e = new MissingDomainException($message);
        $this->assertEquals(
            'Could not find a domain for WPMDB PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $e->getMessage()
        );
    }
}
