<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\Outcome;
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
        // Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        //$source_resource_link = $user->getResourceLink();
        //$url = $source_resource_link->getSetting('lis_outcome_service_url');

        return 'replaceResult';
    }

    protected function getUrl()
    {
        return ''; // TODO: $this->getSetting('lis_outcome_service_url');
    }

    protected function getBody()
    {
        $sourcedId = htmlentities($this->user->ltiResultSourcedId);
        $language = $this->outcome->language;
        $value = $this->outcome->getValue() ?: '';

        // TODO: Verify the value is a valid decimal (self::EXT_TYPE_DECIMAL)

        $xml = <<<EOF
<replaceResultRequest>
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
</replaceResultRequest>
EOF;

        return $xml;
    }

    public function handleNodes(array $nodes)
    {
        return true;
    }
}
