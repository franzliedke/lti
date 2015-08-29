<?php

namespace Franzl\Lti\Http;

interface ResponseInterface
{
    /**
     * Determine whether the response was successful.
     *
     * @return bool
     */
    public function isSuccessful();

    /**
     * Return the wrapped PSR response.
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getWrappedResponse();
}
