<?php

namespace Franzl\Lti\Action;

use Franzl\Lti\ResourceLink;
use Franzl\Lti\ToolProvider;
use Franzl\Lti\User;

class ReadMemberships implements Action
{
    protected $withGroups = false;

    protected $groupSets = [];

    protected $groups = [];

    public function getServiceName()
    {
        return 'basic-lis-readmembershipsforcontext' . ($this->withGroups ? 'withgroups' : '');
    }

    public function getBody()
    {
        // TODO: Implement getBody() method.
    }

    public function handleResponse(array $nodes, ResourceLink $link)
    {
        //$old_users = $this->getUserResultSourcedIDs(true, ToolProvider::ID_SCOPE_RESOURCE);
        $users = [];
        if (!isset($nodes['memberships']['member'])) {
            $members = [];
        } else if (!isset($nodes['memberships']['member'][0])) {
            $members = [];
            $members[0] = $nodes['memberships']['member'];
        } else {
            $members = $nodes['memberships']['member'];
        }

        for ($i = 0; $i < count($members); $i++) {
            $user = new User($this, $members[$i]['user_id']);

            // Set the user name
            $firstname = (isset($members[$i]['person_name_given'])) ? $members[$i]['person_name_given'] : '';
            $lastname = (isset($members[$i]['person_name_family'])) ? $members[$i]['person_name_family'] : '';
            $fullname = (isset($members[$i]['person_name_full'])) ? $members[$i]['person_name_full'] : '';
            $user->setNames($firstname, $lastname, $fullname);

            // Set the user email
            $email = (isset($members[$i]['person_contact_email_primary'])) ? $members[$i]['person_contact_email_primary'] : '';
            $user->setEmail($email, $this->consumer->defaultEmail);

            // Set the user roles
            if (isset($members[$i]['roles'])) {
                $user->roles = ToolProvider::parseRoles($members[$i]['roles']);
            }

            // Set the user groups
            if (!isset($members[$i]['groups']['group'])) {
                $groups = [];
            } else if (!isset($members[$i]['groups']['group'][0])) {
                $groups = [];
                $groups[0] = $members[$i]['groups']['group'];
            } else {
                $groups = $members[$i]['groups']['group'];
            }
            for ($j = 0; $j < count($groups); $j++) {
                $group = $groups[$j];
                if (isset($group['set'])) {
                    $set_id = $group['set']['id'];
                    if (!isset($this->groupSets[$set_id])) {
                        $this->groupSets[$set_id] = ['title' => $group['set']['title'], 'groups' => [],
                                                     'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0];
                    }
                    $this->groupSets[$set_id]['num_members']++;
                    if ($user->isStaff()) {
                        $this->groupSets[$set_id]['num_staff']++;
                    }
                    if ($user->isLearner()) {
                        $this->groupSets[$set_id]['num_learners']++;
                    }
                    if (!in_array($group['id'], $this->groupSets[$set_id]['groups'])) {
                        $this->groupSets[$set_id]['groups'][] = $group['id'];
                    }
                    $this->groups[$group['id']] = ['title' => $group['title'], 'set' => $set_id];
                } else {
                    $this->groups[$group['id']] = ['title' => $group['title']];
                }
                $user->groups[] = $group['id'];
            }

            // If a result sourcedid is provided save the user
            if (isset($members[$i]['lis_result_sourcedid'])) {
                $user->ltiResultSourcedId = $members[$i]['lis_result_sourcedid'];
                $user->save();
            }
            $users[] = $user;

            // Remove old user (if it exists)
            //unset($old_users[$user->getId(ToolProvider::ID_SCOPE_RESOURCE)]);
        }

        // Delete any old users which were not in the latest list from the tool consumer
        //foreach ($old_users as $id => $user) {
        //    $user->delete();
        //}
    }
}
