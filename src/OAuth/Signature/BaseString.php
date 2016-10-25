<?php

namespace Franzl\Lti\OAuth\Signature;

use Franzl\Lti\OAuth\Util;
use Psr\Http\Message\RequestInterface;

class BaseString
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $additionalParams;

    public function __construct(RequestInterface $request, array $params)
    {
        $this->request = $request;
        $this->additionalParams = $params;
    }

    /**
     * Return the base string for the given request
     *
     * The base string is defined as the method, the url
     * and the parameters (normalized), each urlencoded
     * and then concatenated with &.
     *
     * @return string
     */
    public function __toString()
    {
        $httpMethod = strtoupper($this->request->getMethod());
        $normalizedUrl = (string) $this->request->getUri()->withQuery('')->withFragment('');
        $parameterString = $this->getSignableParameters();

        $parts = [
            $httpMethod,
            rawurlencode($normalizedUrl),
            rawurlencode($parameterString)
        ];

        return implode('&', $parts);
    }

    /**
     * The request parameters, sorted and concatenated into a normalized string.
     *
     * @return string
     */
    private function getSignableParameters()
    {
        // Grab all parameters
        $params = $this->request->getUri()->getQuery() . '&' . $this->request->getBody()->__toString();
        $params = Util::parseParameters($params);

        // Remove oauth_signature if present
        // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

        $params = array_merge($params, $this->additionalParams);

        return Util::buildHttpQuery($params);
    }
}
