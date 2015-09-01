<?php

namespace Franzl\Lti\OAuth;

class Server
{
    protected $timestamp_threshold = 300; // in seconds, five minutes
    protected $version = '1.0';             // hi blaine
    protected $signature_methods = [];

    protected $data_store;

    public function __construct($data_store)
    {
        $this->data_store = $data_store;
    }

    public function addSignatureMethod($signature_method)
    {
        $this->signature_methods[$signature_method->getName()] =
            $signature_method;
    }

    // high level functions

    /**
     * process a request_token request
     * returns the request token on success
     */
    public function fetchRequestToken(&$request)
    {
        $this->getVersion($request);

        $consumer = $this->getConsumer($request);

        // no token required for the initial token request
        $token = null;

        $this->checkSignature($request, $consumer, $token);

        // Rev A change
        $callback = $request->get_parameter('oauth_callback');
        $new_token = $this->data_store->new_request_token($consumer, $callback);

        return $new_token;
    }

    /**
     * process an access_token request
     * returns the access token on success
     */
    public function fetchAccessToken(&$request)
    {
        $this->getVersion($request);

        $consumer = $this->getConsumer($request);

        // requires authorized request token
        $token = $this->getToken($request, $consumer, "request");

        $this->checkSignature($request, $consumer, $token);

        // Rev A change
        $verifier = $request->get_parameter('oauth_verifier');
        $new_token = $this->data_store->new_access_token($token, $consumer, $verifier);

        return $new_token;
    }

    /**
     * verify an api call, checks all the parameters
     */
    public function verifyRequest(&$request)
    {
        $this->getVersion($request);
        $consumer = $this->getConsumer($request);
        $token = $this->getToken($request, $consumer, "access");
        $this->checkSignature($request, $consumer, $token);
        return [$consumer, $token];
    }

    // Internals from here
    /**
     * version 1
     */
    private function getVersion(&$request)
    {
        $version = $request->getParameter("oauth_version");
        if (!$version) {
            // Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
            // Chapter 7.0 ("Accessing Protected Ressources")
            $version = '1.0';
        }
        if ($version !== $this->version) {
            throw new Exception("OAuth version '$version' not supported");
        }
        return $version;
    }

    /**
     * figure out the signature with some defaults
     */
    private function getSignatureMethod($request)
    {
        $signature_method = $request instanceof Request
            ? $request->getParameter("oauth_signature_method")
            : null;

        if (!$signature_method) {
            // According to chapter 7 ("Accessing Protected Ressources") the signature-method
            // parameter is required, and we can't just fallback to PLAINTEXT
            throw new Exception('No signature method parameter. This parameter is required');
        }

        if (!in_array(
            $signature_method,
            array_keys($this->signature_methods)
        )) {
            throw new Exception(
                "Signature method '$signature_method' not supported " .
                "try one of the following: " .
                implode(", ", array_keys($this->signature_methods))
            );
        }
        return $this->signature_methods[$signature_method];
    }

    /**
     * try to find the consumer for the provided request's consumer key
     */
    private function getConsumer($request)
    {
        $consumer_key = $request instanceof Request
            ? $request->getParameter("oauth_consumer_key")
            : null;

        if (!$consumer_key) {
            throw new Exception("Invalid consumer key");
        }

        $consumer = $this->data_store->lookupConsumer($consumer_key);
        if (!$consumer) {
            throw new Exception("Invalid consumer");
        }

        return $consumer;
    }

    /**
     * try to find the token for the provided request's token key
     */
    private function getToken($request, $consumer, $token_type = "access")
    {
        $token_field = $request instanceof Request
            ? $request->getParameter('oauth_token')
            : null;

        $token = $this->data_store->lookupToken(
            $consumer,
            $token_type,
            $token_field
        );
        if (!$token) {
            throw new Exception("Invalid $token_type token: $token_field");
        }
        return $token;
    }

    /**
     * all-in-one function to check the signature on a request
     * should guess the signature method appropriately
     */
    private function checkSignature($request, $consumer, $token)
    {
        // this should probably be in a different method
        $timestamp = $request instanceof Request
            ? $request->getParameter('oauth_timestamp')
            : null;
        $nonce = $request instanceof Request
            ? $request->getParameter('oauth_nonce')
            : null;

        $this->checkTimestamp($timestamp);
        $this->checkNonce($consumer, $token, $nonce, $timestamp);

        $signature_method = $this->getSignatureMethod($request);

        $signature = $request->getParameter('oauth_signature');
        $valid_sig = $signature_method->checkSignature(
            $request,
            $consumer,
            $token,
            $signature
        );

        if (!$valid_sig) {
            throw new Exception("Invalid signature");
        }
    }

    /**
     * check that the timestamp is new enough
     */
    private function checkTimestamp($timestamp)
    {
        if (! $timestamp) {
            throw new Exception(
                'Missing timestamp parameter. The parameter is required'
            );
        }

        // verify that timestamp is recentish
        $now = time();
        if (abs($now - $timestamp) > $this->timestamp_threshold) {
            throw new Exception(
                "Expired timestamp, yours $timestamp, ours $now"
            );
        }
    }

    /**
     * check that the nonce is not repeated
     */
    private function checkNonce($consumer, $token, $nonce, $timestamp)
    {
        if (! $nonce) {
            throw new Exception(
                'Missing nonce parameter. The parameter is required'
            );
        }

        // verify that the nonce is uniqueish
        $found = $this->data_store->lookupNonce(
            $consumer,
            $token,
            $nonce,
            $timestamp
        );
        if ($found) {
            throw new Exception("Nonce already used: $nonce");
        }
    }
}
