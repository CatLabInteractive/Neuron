<?php

namespace Neuron\Exceptions;

use Neuron\Net\Response;

/**
 * Class ResponseException
 *
 * Abort the current request and instead return this response.
 *
 * @package Neuron\Exceptions
 */
class ResponseException extends \Exception
{
    protected $response;

    /**
     * ResponseException constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
        parent::__construct('Response exception', $response->getStatus());
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}