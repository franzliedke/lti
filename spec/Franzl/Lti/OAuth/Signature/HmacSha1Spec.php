<?php

namespace spec\Franzl\Lti\OAuth\Signature;

use Franzl\Lti\OAuth\Consumer;
use Franzl\Lti\OAuth\Token;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HmacSha1Spec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Franzl\Lti\OAuth\Signature\HmacSha1');
    }

    function it_calculates_the_correct_signature()
    {
        $request = new \Zend\Diactoros\Request(
            'https://api.twitter.com/1/statuses/update.json?include_entities=true',
            'POST'
        );

        $request->getBody()->write('status=Hello%20Ladies%20%2b%20Gentlemen%2c%20a%20signed%20OAuth%20request%21');

        $consumer = new Consumer('xvz1evFS4wEEPTGEFPHBog', 'kAcSOqF21Fu85e7zjz7ZN2U4ZRhfV3WpwPAoE3Z7kBw');
        $token = new Token('370773112-GmHxMAgYyLbNEtIKZeRNFsMKPR9EyMZeS9weJAEb', 'LswwdoUaIvS8ltyTt5jkRh4J50vUPVVHtR2YPi5kE');

        $params = [
            'oauth_version' => '1.0',
            'oauth_consumer_key' => $consumer->key,
            'oauth_nonce' => 'kYjzVBB8Y0ZFabxSWbWovY3uYSQ2pTgmZeNu2VS4cg',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => '1318622958',
            'oauth_token' => $token->key,
        ];

        $exampleFromTwitter = 'tnnArxj06cWHq44gCs1OSKk/jLY=';

        $this->buildSignature(
            $request,
            $params,
            $consumer,
            $token
        )->shouldReturn($exampleFromTwitter);
    }
}
