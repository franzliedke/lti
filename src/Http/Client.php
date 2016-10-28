<?php

namespace Franzl\Lti\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface Client
{
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request);

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendSigned(RequestInterface $request);
}
