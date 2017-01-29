<?php
namespace Mill\Exceptions;

class MethodNotSuppliedException extends \Exception
{
    /**
     * @var string
     */
    protected $message = 'No method was supplied.';
}
