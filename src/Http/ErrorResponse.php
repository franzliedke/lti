<?php

namespace Franzl\Lti\Http;

class ErrorResponse implements ResponseInterface
{
    /**
     * Determine whether the response was successful.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return false;
    }

    /**
     * Return the wrapped PSR response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getWrappedResponse()
    {
        return null;
    }
}
