<?php

namespace Franzl\Lti\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class ClientFactory
{
    public static function make()
    {
        $stack = HandlerStack::create();
        $stack->push(new Oauth1([
            'consumer_key'    => 'abc',
            'consumer_secret' => 'secret',
        ]));

        return new GuzzleClient(new Client(['handler' => $stack]));
    }
}
