<?php

namespace Franzl\Lti\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;

class ClientFactory
{
    public static function make()
    {
        $stack = HandlerStack::create(new Oauth1([
            'consumer_key'    => 'my_key',
            'consumer_secret' => 'my_secret',
        ]));

        return new GuzzleClient(new Client(['handler' => $stack]));
    }
}
