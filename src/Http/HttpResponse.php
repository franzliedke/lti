<?php

namespace Franzl\Lti\Http;

use Psr\Http\Message\ResponseInterface as PsrResponse;

class HttpResponse implements ResponseInterface
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    protected $wrapped;

    /**
     * Instantiate the response.
     *
     * @param \Psr\Http\Message\ResponseInterface $wrapped
     */
    public function __construct(PsrResponse $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    /**
     * Determine whether the response was successful.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->wrapped->getStatusCode() < 400;
    }

    /**
     * Return the wrapped PSR response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getWrappedResponse()
    {
        return $this->wrapped;
    }
}
