<?php

namespace Franzl\Lti\Action;

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

        // Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        //$source_resource_link = $user->getResourceLink();
        //$url = $source_resource_link->getSetting('lis_outcome_service_url');

        return 'deleteResult';
    }

    protected function getUrl()
    {
        return ''; // TODO: $this->getSetting('lis_outcome_service_url');
    }

    protected function getBody()
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

        return $xml;
    }

    public function handleNodes(array $nodes)
    {
        return true;
    }
}
