<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ToolProvider;
use Franzl\Lti\User;

class ReadMemberships extends LTI1Action implements Action
{
    public function getServiceName()
    {
        return 'basic-lis-readmembershipsforcontextwithgroups';
        // TODO: Or basic-lis-readmembershipsforcontext (if requested without groups)
    }

    protected function getUrl()
    {
        return ''; // TODO: $this->getSetting('ext_ims_lis_memberships_url');
    }

    protected function getParams()
    {
        /*
        $params = [];
        $params['id'] = $this->getSetting('ext_ims_lis_memberships_id');
         */
        return [];
    }

    protected function handleNodes(array $nodes)
    {
        if (!isset($nodes['memberships']['member'])) {
            $members = [];
        } else if (!isset($nodes['memberships']['member'][0])) {
            $members = [];
            $members[0] = $nodes['memberships']['member'];
        } else {
            $members = $nodes['memberships']['member'];
        }

        foreach ($members as $member) {
            $user = new User($this, $member['user_id']);

            // Set the user name
            $firstName = (isset($member['person_name_given'])) ? $member['person_name_given'] : '';
            $lastName = (isset($member['person_name_family'])) ? $member['person_name_family'] : '';
            $fullName = (isset($member['person_name_full'])) ? $member['person_name_full'] : '';
            $user->setNames($firstName, $lastName, $fullName);

            // Set the user email
            $email = (isset($member['person_contact_email_primary'])) ? $member['person_contact_email_primary'] : '';
            $user->setEmail($email, $this->consumer->defaultEmail);

            // Set the user roles
            if (isset($member['roles'])) {
                $user->roles = ToolProvider::parseRoles($member['roles']);
            }

            // Set the user groups
            if (!isset($member['groups']['group'])) {
                $groups = [];
            } else if (!isset($member['groups']['group'][0])) {
                $groups = [];
                $groups[0] = $member['groups']['group'];
            } else {
                $groups = $member['groups']['group'];
            }

            foreach ($groups as $group) {
                $user->groups[] = $group['id'];
            }

            $users[] = $user;
        }
    }
}
