<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Http\Client;

interface Action
{
    public function run(Client $client);
}
