<?php

namespace Franzl\Lti\Http;

use GuzzleHttp\ClientInterface as GuzzleContract;
use GuzzleHttp\Psr7\Request;

class GuzzleClient implements ClientInterface
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;

    /**
     * Instantiate the client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     */
    public function __construct(GuzzleContract $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Send a HTTP request.
     *
     * @param string $url
     * @param string $method
     * @param array|string $body
     * @param array $headers
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($url, $method, $body, $headers = [])
    {
        if (is_array($body)) {
            $body = http_build_query($body);
        }
        $request = new Request($method, $url, $headers, $body);
        return $this->guzzle->send($request);
    }
}
