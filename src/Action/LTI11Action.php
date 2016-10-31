<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Http\Client;
use Franzl\Lti\Parse\XML;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

abstract class LTI11Action implements Action
{
    public function run(Client $client)
    {
        $request = $this->makeRequest();

        $response = $client->sendSigned($request);

        $this->handleResponse($response);
    }

    protected function makeRequest()
    {
        // TODO: Calculate body hash
        //$hash = base64_encode(sha1($xmlRequest, true));
        //$params = ['oauth_body_hash' => $hash];
        $request = new Request(
            'POST',
            $this->getUrl(),
            ['content-type' => 'application/xml']
        );

        $request->getBody()->write(
            $this->wrapInEnvelope($this->getBody())
        );

        return $request;
    }

    abstract protected function getUrl();

    abstract protected function getBody();

    protected function wrapInEnvelope($xml)
    {
        $id = uniqid();
        $envelope = <<< EOD
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

        return $envelope;
    }

    protected function handleResponse(ResponseInterface $response)
    {
        $xml = (string) $response->getBody();
        $nodes = XML::extractNodes($xml);

        if (isset($nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor']) &&
            ($nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor'] == 'success')) {
            $ok = true;
        }

        $this->handleNodes($nodes);
    }

    abstract protected function handleNodes(array $nodes);
}
