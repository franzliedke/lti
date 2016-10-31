<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Http\Client;
use Franzl\Lti\Parse\XML;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

abstract class LTI1Action implements Action
{
    public function run(Client $client)
    {
        $request = $this->makeRequest();

        $response = $client->sendSigned($request);

        $this->handleResponse($response);
    }

    protected function makeRequest()
    {
        $request = new Request(
            'POST',
            $this->getUrl(),
            ['content-type' => 'application/x-www-form-urlencoded']
        );

        $request->getBody()->write(
            http_build_query($this->getParams())
        );

        return $request;
    }

    abstract protected function getUrl();

    abstract protected function getParams();

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
