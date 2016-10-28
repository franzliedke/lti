<?php

namespace Franzl\Lti\Http;

use GuzzleHttp\Client;

class ClientFactory
{
    public static function make()
    {
        return new GuzzleClient(new Client);
    }
}
