<?php

namespace Franzl\Lti\Action;

abstract class LTI11Action implements Action
{
    public function getContentType()
    {
        return 'application/xml';
    }

    protected function wrapXML($xml)
    {
        $id = uniqid();
        $request = <<< EOD
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>{$id}</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
{$xml}
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>
EOD;

        return $request;
    }
}
