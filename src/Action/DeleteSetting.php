<?php

namespace Franzl\Lti\Action;

class DeleteSetting extends LTI1Action implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-deletesetting';
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
        return true;
    }
}
