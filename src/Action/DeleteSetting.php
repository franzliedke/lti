<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;

class DeleteSetting implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-deletesetting';
    }

    public function getBody()
    {
        // TODO: Implement getBody() method.
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        return true;
    }
}
