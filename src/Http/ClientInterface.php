<?php

namespace Franzl\Lti\Http;

interface ClientInterface
{
    /**
     * Send a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array|string $body
     * @param array $headers
     * @return ResponseInterface
     */
    public function send($url, $method, $body, $headers = []);
}
