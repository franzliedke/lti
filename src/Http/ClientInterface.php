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
     * @param array $options
     * @return ResponseInterface
     */
    public function send($url, $method, $body, $headers = [], $options = []);

    /**
     * Send a HTTP request, signed with OAuth.
     *
     * @param string $url
     * @param string $method
     * @param array|string $body
     * @param array $headers
     * @return ResponseInterface
     */
    public function sendSigned($url, $method, $body, $headers = []);
}
