<?php
class MlabFactoryApiException extends Exception
{
    /** @var int */
    protected $statusCode;

    /** @var array */
    protected $details;

    public function __construct($message, $statusCode = 400, array $details = array(), $code = 0, $previous = null)
    {
        $this->statusCode = (int) $statusCode;
        $this->details = $details;

        parent::__construct($message, (int) $code, $previous);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getDetails()
    {
        return $this->details;
    }
}
