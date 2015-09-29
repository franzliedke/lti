<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Outcome;
use Franzl\Lti\ResourceLink;
use Franzl\Lti\User;

class ReadResult extends LTI11Action implements Action
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
        // if ($lti_outcome->type == self::EXT_TYPE_DECIMAL)
        return 'readResult';
    }

    public function getBody()
    {
        $sourcedId = htmlentities($this->user->ltiResultSourcedId);

        $xml = <<<EOF
<readResultRequest>
    <resultRecord>
        <sourcedGUID>
            <sourcedId>{$sourcedId}</sourcedId>
        </sourcedGUID>
    </resultRecord>
</readResultRequest>
EOF;

        return $this->wrapXML($xml);
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        $value = array_get($nodes, 'imsx_POXBody.readResultResponse.result.resultScore.textString');

        if ($value) {
            $this->outcome->setValue($value);
        }

        return true;
    }
}
