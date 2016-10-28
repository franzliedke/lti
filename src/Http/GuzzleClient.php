<?php

namespace Franzl\Lti\Http;

use Franzl\Lti\OAuth\Signer;
use GuzzleHttp\ClientInterface as GuzzleContract;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleClient implements Client
{
    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $guzzle;

    /**
     * @var Signer
     */
    protected $signer;

    /**
     * Instantiate the client.
     *
     * @param \GuzzleHttp\ClientInterface $guzzle
     * @param Signer $signer
     */
    public function __construct(GuzzleContract $guzzle, Signer $signer)
    {
        $this->guzzle = $guzzle;
        $this->signer = $signer;
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function send(RequestInterface $request)
    {
        return $this->guzzle->send($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function sendSigned(RequestInterface $request)
    {
        return $this->guzzle->send(
            $this->signer->sign($request)
        );
    }
}
