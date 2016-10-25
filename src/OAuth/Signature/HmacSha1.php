<?php

namespace Franzl\Lti\OAuth\Signature;

use Franzl\Lti\OAuth\Consumer;
use Franzl\Lti\OAuth\Token;
use Franzl\Lti\OAuth\Util;
use Psr\Http\Message\RequestInterface;

/**
 * The HMAC-SHA1 signature method uses the HMAC-SHA1 signature algorithm as defined in [RFC2104]
 * where the Signature Base String is the text and the key is the concatenated values (each first
 * encoded per Parameter Encoding) of the Consumer Secret and Token Secret, separated by an '&'
 * character (ASCII code 38) even if empty.
 *   - Chapter 9.2 ("HMAC-SHA1")
 */
class HmacSha1 extends SignatureMethod
{
    public function getName()
    {
        return "HMAC-SHA1";
    }

    public function buildSignature(RequestInterface $request, array $params, Consumer $consumer, Token $token)
    {
        $baseString = new BaseString($request, $params);

        $keyParts = [
            $consumer->secret,
            $token ? $token->secret : ''
        ];

        $keyParts = Util::urlencodeRfc3986($keyParts);
        $key = implode('&', $keyParts);

        return base64_encode(hash_hmac('sha1', (string) $baseString, $key, true));
    }
}
