<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;

class WriteSetting implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-savesetting';
    }

    public function asXML()
    {
        // TODO: Implement asXML() method.
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        $link->setSetting('ext_ims_lti_tool_setting', $this->getSetting());
        $link->saveSettings();

        return true;
    }

    protected function getSetting()
    {
        // TODO: Pass this in ($value in ResourceLink::doSettingService())
        return '';
    }
}
