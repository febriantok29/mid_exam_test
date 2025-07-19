<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Validation\Validator;

class ValidatorException extends Exception
{
    protected $errors;

    public function __construct($message = "Validation failed", $errors = null)
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function errors()
    {
        return $this->errors;
    }
}
