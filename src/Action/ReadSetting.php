<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;

class ReadSetting implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-loadsetting';
    }

    public function getBody()
    {
        // TODO: Implement getBody() method.
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        if (isset($nodes['setting']['value']) && !is_array($nodes['setting']['value'])) {
            return $nodes['setting']['value'];
        }

        return '';
    }
}
