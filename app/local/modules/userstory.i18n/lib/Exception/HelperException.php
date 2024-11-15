<?php

namespace Userstory\I18n\Exception;

use Exception;
use Throwable;

/**
 * Class HelperException
 * 
 * @package Userstory\I18n\Exception
 */
class HelperException extends Exception
{
    /**
     * HelperException constructor
     * 
     * @param string|array $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = is_array($message) ? implode(', ', $message) : $message;

        parent::__construct($message, $code, $previous);
    }
}
