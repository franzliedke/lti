<?php

namespace Franzl\Lti\OAuth;

use Franzl\Lti\OAuth\Signature\SignatureMethod;
use Psr\Http\Message\RequestInterface;

class Signer
{
    /**
     * @var SignatureMethod
     */
    protected $signatureMethod;

    /**
     * @var Consumer
     */
    protected $consumer;

    /**
     * @var Token
     */
    protected $token;

    public function __construct(SignatureMethod $signatureMethod, Consumer $consumer, Token $token)
    {
        $this->signatureMethod = $signatureMethod;
        $this->consumer = $consumer;
        $this->token = $token;
    }

    /**
     * Sign the given request and return the signed request.
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    public function sign(RequestInterface $request)
    {
        $oAuthParams = array_merge(
            $this->getDefaultOAuthParams(),
            ['oauth_signature_method' => $this->signatureMethod->getName()]
        );

        $oAuthParams['oauth_signature'] = $this->signatureMethod->buildSignature(
            $request,
            $oAuthParams,
            $this->consumer,
            $this->token
        );

        return $request->withHeader(
            'authorization',
            $this->buildAuthorizationHeader($oAuthParams)
        );
    }

    private function getDefaultOAuthParams()
    {
        return [
            'oauth_nonce'        => md5(microtime() . mt_rand()),
            'oauth_timestamp'    => time(),
            'oauth_version'      => '1.0',
            'oauth_consumer_key' => $this->consumer->key,
            'oauth_token'        => $this->token->key,
        ];
    }

    private function buildAuthorizationHeader(array $params)
    {
        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . implode(', ', $parts);
    }
}
