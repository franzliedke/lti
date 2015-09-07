<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;

interface Action
{
    public function getServiceName();

    public function asXML();

    public function handleResponse(array $nodes, ResourceLink $link);
}
