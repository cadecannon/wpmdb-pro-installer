<?php

namespace CadeCannon\WPMDBProInstaller\Test\Exceptions;

use CadeCannon\WPMDBProInstaller\Exceptions\MissingKeyException;

class MissingKeyExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testMessage()
    {
        $message = 'FIELD';
        $e = new MissingKeyException($message);
        $this->assertEquals(
            'Could not find a key for WPMDB PRO. ' .
            'Please make it available via the environment variable ' .
            $message,
            $e->getMessage()
        );
    }
}
