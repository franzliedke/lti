<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;

class DeleteSetting implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-deletesetting';
    }

    public function asXML()
    {
        // TODO: Implement asXML() method.
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        return true;
    }
}
