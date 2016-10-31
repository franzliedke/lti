<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Outcome;
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
        // Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        //$source_resource_link = $user->getResourceLink();
        //$url = $source_resource_link->getSetting('lis_outcome_service_url');

        return 'readResult';
    }

    protected function getUrl()
    {
        return ''; // TODO: $this->getSetting('lis_outcome_service_url');
    }

    protected function getBody()
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

        return $xml;
    }

    public function handleNodes(array $nodes)
    {
        if (isset($nodes['imsx_POXBody']['readResultResponse']['result']['resultScore']['textString'])) {
            $this->outcome->setValue(
                $nodes['imsx_POXBody']['readResultResponse']['result']['resultScore']['textString']
            );
            return true;
        }

        return false;
    }
}
