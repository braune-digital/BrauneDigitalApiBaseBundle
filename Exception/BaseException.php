<?php

namespace BrauneDigital\ApiBaseBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class BaseException extends HttpException
{
    /**
     *  Constructor for REST-Exceptions
     * @param string $statusCode
     * @param null $message
     * @param \Exception $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct($message = null, $statusCode = '403', \Exception $previous = null, array $headers = array(), $code = 0)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}