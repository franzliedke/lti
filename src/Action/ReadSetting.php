<?php

namespace Franzl\Lti\Action;

class ReadSetting extends LTI1Action implements Action
{
    public function getServiceName()
    {
        return 'basic-lti-loadsetting';
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
        if (isset($nodes['setting']['value']) && !is_array($nodes['setting']['value'])) {
            return $nodes['setting']['value'];
        }

        return '';
    }
}
