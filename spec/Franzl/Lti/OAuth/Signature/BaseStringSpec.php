<?php

namespace spec\Franzl\Lti\OAuth\Signature;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BaseStringSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Franzl\Lti\OAuth\Signature\BaseString');
    }

    function let()
    {
        $request = new \Zend\Diactoros\Request(
            'https://api.twitter.com/1/statuses/update.json?include_entities=true',
            'POST'
        );

        $request->getBody()->write('status=Hello%20Ladies%20%2b%20Gentlemen%2c%20a%20signed%20OAuth%20request%21');

        $params = [
            'oauth_version' => '1.0',
            'oauth_consumer_key' => 'xvz1evFS4wEEPTGEFPHBog',
            'oauth_nonce' => 'kYjzVBB8Y0ZFabxSWbWovY3uYSQ2pTgmZeNu2VS4cg',
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => '1318622958',
            'oauth_token' => '370773112-GmHxMAgYyLbNEtIKZeRNFsMKPR9EyMZeS9weJAEb',
        ];

        $this->beConstructedWith($request, $params);
    }

    function it_calculates_the_correct_base_string()
    {
        $exampleFromTwitter = 'POST&https%3A%2F%2Fapi.twitter.com%2F1%2Fstatuses%2Fupdate.json&include_entities%3Dtrue%26oauth_consumer_key%3Dxvz1evFS4wEEPTGEFPHBog%26oauth_nonce%3DkYjzVBB8Y0ZFabxSWbWovY3uYSQ2pTgmZeNu2VS4cg%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1318622958%26oauth_token%3D370773112-GmHxMAgYyLbNEtIKZeRNFsMKPR9EyMZeS9weJAEb%26oauth_version%3D1.0%26status%3DHello%2520Ladies%2520%252B%2520Gentlemen%252C%2520a%2520signed%2520OAuth%2520request%2521';

        $this->__toString()->shouldReturn($exampleFromTwitter);
    }
}
