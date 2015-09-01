<?php

namespace spec\Franzl\Lti;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;

class ToolProviderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Franzl\Lti\ToolProvider');
    }

    function it_should_fail_for_incomplete_requests(ServerRequestInterface $request)
    {
        $this->shouldThrow('\Exception')->duringHandleRequest($request);
    }
}
