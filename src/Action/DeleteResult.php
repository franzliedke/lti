<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;
use Franzl\Lti\User;

class DeleteResult extends LTI11Action implements Action
{
    /**
     * @var User
     */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getServiceName()
    {
        // if ($lti_outcome->type == self::EXT_TYPE_DECIMAL) {
        return 'deleteResult';
    }

    public function getBody()
    {
        $sourcedId = htmlentities($this->user->ltiResultSourcedId);

        $xml = <<<EOF
<deleteResultRequest>
    <resultRecord>
        <sourcedGUID>
            <sourcedId>{$sourcedId}</sourcedId>
        </sourcedGUID>
    </resultRecord>
</deleteResultRequest>
EOF;

        return $this->wrapXML($xml);
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        return true;
    }
}
