<?php

namespace Franzl\Lti\Action;

abstract class LTI1Action implements Action
{
    public function getContentType()
    {
        return 'application/x-www-form-urlencoded';
    }

    abstract protected function getParams();

    public function getBody()
    {
        $body = $this->getParams();
        return http_build_query($body);
    }
}
