<?php

namespace IntraLibrary;

use \Exception;

/**
 * IntraLibraryException class
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryException extends Exception
{
    const USER_EXISTS = 1;

    /*
     * regex string index in this array must match the constant value
     * ie. USER_EXISTS regex is at index 0
     */
    private static $exceptionRegExes = array(
        'INDEX OFFSET BY ONE',
        '/A User with username \"(.)*\" already exists, please use a different username./',
    );

    /**
     * Create an IntraLibraryException object
     *
     * @param string  $message the message
     * @param integer $code    the exception code (pass -1 to autodetect the code based on message)
     */
    public function __construct($message, $code = 0)
    {
        if ($code === -1) {
            if (preg_match(self::$exceptionRegExes[self::USER_EXISTS], $message)) {
                $code = self::USER_EXISTS;
            }
        }

        parent::__construct($message, $code);
    }
}

