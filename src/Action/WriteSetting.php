<?php

namespace Franzl\Lti\Action;

class WriteSetting extends LTI1Action implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-savesetting';
    }

    protected function getUrl()
    {
        return ''; // TODO: $this->getSetting('ext_ims_lti_tool_setting_url');
    }

    protected function getParams()
    {
        /*
        $name = $action->getServiceName();

        $url = $this->getSetting('ext_ims_lti_tool_setting_url');
        $params = [
            'id' => $this->getSetting('ext_ims_lti_tool_setting_id'),
            'setting' => $value ?: '',
        ];
         */
        return [];
    }

    protected function handleNodes(array $nodes)
    {
        //$link->setSetting('ext_ims_lti_tool_setting', $this->getSetting());
        //$link->saveSettings();

        return true;
    }

    protected function getSetting()
    {
        // TODO: Pass this in ($value in ResourceLink::doSettingService())
        return '';
    }
}
