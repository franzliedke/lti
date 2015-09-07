<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Outcome;
use Franzl\Lti\ResourceLink;
use Franzl\Lti\User;

class WriteResult extends LTI11Action implements Action
{
    /**
     * @var Outcome
     */
    protected $outcome;

    /**
     * @var User
     */
    protected $user;

    public function __construct(Outcome $outcome, User $user)
    {
        $this->outcome = $outcome;
        $this->user = $user;
    }

    public function getServiceName()
    {
        // if ($this->checkValueType($lti_outcome, [self::EXT_TYPE_DECIMAL])) {
        return 'replaceResult';
    }

    public function asXML()
    {
        $sourcedId = htmlentities($this->user->ltiResultSourcedId);
        $language = $this->outcome->language;
        $value = $this->outcome->getValue() ?: '';

        $xml = <<<EOF
<resultRecord>
    <sourcedGUID>
        <sourcedId>{$sourcedId}</sourcedId>
    </sourcedGUID>
    <result>
        <resultScore>
            <language>{$language}</language>
            <textString>{$value}</textString>
        </resultScore>
    </result>
</resultRecord>
EOF;

        return $this->wrapXML($xml);
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        return true;
    }
}
