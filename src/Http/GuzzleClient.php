<?php

namespace Franzl\Lti\Http;

use GuzzleHttp\ClientInterface as GuzzleContract;
use GuzzleHttp\Exception\TransferException;
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
     * @param array $options
     * @return ResponseInterface
     */
    public function send($url, $method, $body, $headers = [], $options = [])
    {
        if (is_array($body)) {
            $body = http_build_query($body);
        }
        $request = new Request($method, $url, $headers, $body);

        try {
            return new HttpResponse($this->guzzle->send($request, $options));
        } catch (TransferException $e) {
            return new ErrorResponse;
        }
    }

    /**
     * Send a HTTP request, signed with OAuth.
     *
     * @param string $url
     * @param string $method
     * @param array|string $body
     * @param array $headers
     * @return ResponseInterface
     */
    public function sendSigned($url, $method, $body, $headers = [])
    {
        return $this->send($url, $method, $body, $headers, ['auth' => 'oauth']);
    }
}
