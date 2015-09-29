<?php

namespace Franzl\Lti\OAuth;

use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

class RequestVerifier
{
    protected $validMethods = [
        'HMAC-SHA1' => SignatureMethodHmacSha1::__CLASS__,
        'RSA-SHA1' => SignatureMethodRsaSha1::__CLASS__,
        'PLAINTEXT' => SignatureMethodPlainText::__CLASS__,
    ];

    public function verify(ServerRequestInterface $request)
    {
        //
        $this->checkSignature($request, $token);

        return [$consumer, $token];

        $this->getVersion($request);
        $consumer = $this->getConsumer($request);
        $token = $this->getToken($request, $consumer, "access");
        $this->checkSignature($request, $consumer, $token);
        return [$consumer, $token];
    }

    protected function checkSignature(ServerRequestInterface $request)
    {
        $timestamp = $request->getAttribute('oauth_timestamp');
        $nonce = $request->getAttribute('oauth_nonce');

        // TODO: Check timestamp
        // TODO: Check nonce

        $signatureMethod = $this->getSignatureMethod($request);

        $requestSignature = $request->getAttribute('oauth_signature');
        $signature = $signatureMethod->build($request);

        if (! Str::equals($requestSignature, $signature)) {
            throw new \Exception('Invalid signature');
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return SignatureMethod
     * @throws Exception
     */
    protected function getSignatureMethod(ServerRequestInterface $request)
    {
        $method = $request->getAttribute('oauth_signature_method');

        if (! $method) {
            throw new Exception('Required signature method parameter is missing.');
        }

        if (! array_key_exists($method, $this->validMethods)) {
            $valid = implode(', ', array_keys($this->validMethods));
            throw new Exception("Signature method '$method' not supported. Try one of the following: $valid.");
        }

        return new $this->validMethods[$method]();
    }
}
