<?php

namespace Franzl\Lti;

/**
 * Class to represent a tool consumer user
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class User
{
    /**
     * User's first name
     *
     * @var string
     */
    public $firstName = '';

    /**
     * User's last name (surname or family name)
     *
     * @var string
     */
    public $lastName = '';

    /**
     * User's full name
     *
     * @var string
     */
    public $fullName = '';

    /**
     * User's email address
     *
     * @var string
     */
    public $email = '';

    /**
     * Roles for user
     *
     * @var array
     */
    public $roles = [];

    /**
     * Groups for user
     *
     * @var array
     */
    public $groups = [];

    /**
     * User's result sourcedid
     *
     * @var string
     */
    public $ltiResultSourcedId = null;

    /**
     * Date/time the record was created
     *
     * @var object
     */
    public $created = null;

    /**
     * Date/time the record was last updated
     *
     * @var object
     */
    public $updated = null;

    /**
     * Resource link object
     *
     * @var ResourceLink
     */
    private $resourceLink = null;

    /**
     * User ID value
     *
     * @var string
     */
    private $id = null;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resourceLink Resource_Link object
     * @param string      $id      User ID value
     */
    public function __construct($resourceLink, $id)
    {
        $this->initialise();
        $this->resourceLink = $resourceLink;
        $this->id = $id;
        $this->load();
    }

    /**
     * Initialise the user.
     */
    public function initialise()
    {
        $this->firstName = '';
        $this->lastName = '';
        $this->fullName = '';
        $this->email = '';
        $this->roles = [];
        $this->groups = [];
        $this->ltiResultSourcedId = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Load the user from the database.
     *
     * @return boolean True if the user object was successfully loaded
     */
    public function load()
    {
        $this->initialise();
        if (!is_null($this->resourceLink)) {
            $this->resourceLink->getConsumer()->getStorage()->userLoad($this);
        }
    }

    /**
     * Save the user to the database.
     *
     * @return boolean True if the user object was successfully saved
     */
    public function save()
    {
        if (!empty($this->ltiResultSourcedId) && !is_null($this->resourceLink)) {
            $ok = $this->resourceLink->getConsumer()->getStorage()->userSave($this);
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Delete the user from the database.
     *
     * @return boolean True if the user object was successfully deleted
     */
    public function delete()
    {
        return
            is_null($this->resourceLink) ||
            $this->resourceLink->getConsumer()->getStorage()->User_delete($this);
    }

    /**
     * Get resource link.
     *
     * @return ResourceLink Resource link object
     */
    public function getResourceLink()
    {
        return $this->resourceLink;
    }

    /**
     * Get the user ID (which may be a compound of the tool consumer and resource link IDs).
     *
     * @param int $idScope Scope to use for user ID (optional, default is null for consumer default setting)
     *
     * @return string User ID value
     */
    public function getId($idScope = null)
    {
        if (empty($idScope)) {
            if (!is_null($this->resourceLink)) {
                $idScope = $this->resourceLink->getConsumer()->id_scope;
            } else {
                $idScope = ToolProvider::ID_SCOPE_ID_ONLY;
            }
        }
        switch ($idScope) {
            case ToolProvider::ID_SCOPE_GLOBAL:
                $id = $this->resourceLink->getKey() . ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            case ToolProvider::ID_SCOPE_CONTEXT:
                $id = $this->resourceLink->getKey();
                if ($this->resourceLink->lti_context_id) {
                    $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->resourceLink->lti_context_id;
                }
                $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            case ToolProvider::ID_SCOPE_RESOURCE:
                $id = $this->resourceLink->getKey();
                if ($this->resourceLink->lti_resource_id) {
                    $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->resourceLink->lti_resource_id;
                }
                $id .= ToolProvider::ID_SCOPE_SEPARATOR . $this->id;
                break;
            default:
                $id = $this->id;
                break;
        }

        return $id;
    }

    /**
     * Set the user's name.
     *
     * @param string $firstName User's first name.
     * @param string $lastName User's last name.
     * @param string $fullName User's full name.
     */
    public function setNames($firstName, $lastName, $fullName)
    {
        $names = [0 => '', 1 => ''];
        if (!empty($fullName)) {
            $this->fullName = trim($fullName);
            $names = preg_split("/[\s]+/", $this->fullName, 2);
        }
        if (!empty($firstName)) {
            $this->firstName = trim($firstName);
            $names[0] = $this->firstName;
        } else if (!empty($names[0])) {
            $this->firstName = $names[0];
        } else {
            $this->firstName = 'User';
        }
        if (!empty($lastName)) {
            $this->lastName = trim($lastName);
            $names[1] = $this->lastName;
        } else if (!empty($names[1])) {
            $this->lastName = $names[1];
        } else {
            $this->lastName = $this->id;
        }
        if (empty($this->fullName)) {
            $this->fullName = "{$this->firstName} {$this->lastName}";
        }
    }

    /**
     * Set the user's email address.
     *
     * @param string $email        Email address value
     * @param string $defaultEmail Value to use if no email is provided (optional, default is none)
     */
    public function setEmail($email, $defaultEmail = null)
    {
        if (!empty($email)) {
            $this->email = $email;
        } else if (!empty($defaultEmail)) {
            $this->email = $defaultEmail;
            if (substr($this->email, 0, 1) == '@') {
                $this->email = $this->getId() . $this->email;
            }
        } else {
            $this->email = '';
        }
    }

    /**
     * Check if the user is an administrator (at any of the system, institution or context levels).
     *
     * @return boolean True if the user has a role of administrator
     */
    public function isAdmin()
    {
        return $this->hasRoles([
            'Administrator',
            'urn:lti:sysrole:ims/lis/SysAdmin',
            'urn:lti:sysrole:ims/lis/Administrator',
            'urn:lti:instrole:ims/lis/Administrator'
        ]);
    }

    /**
     * Check if the user is staff.
     *
     * @return boolean True if the user has a role of instructor, contentdeveloper or teachingassistant
     */
    public function isStaff()
    {
        return $this->hasRoles(['Instructor', 'ContentDeveloper', 'TeachingAssistant']);
    }

    /**
     * Check if the user is a learner.
     *
     * @return boolean True if the user has a role of learner
     */
    public function isLearner()
    {
        return $this->hasRole('Learner');
    }

    /**
     * Check whether the user has a specified role name.
     *
     * @param string $role Name of role
     *
     * @return boolean True if the user has the specified role
     */
    private function hasRole($role)
    {
        if (substr($role, 0, 4) != 'urn:') {
            $role = "urn:lti:role:ims/lis/$role";
        }

        return in_array($role, $this->roles);
    }

    private function hasRoles(array $roles)
    {
        $myRoles = array_filter($roles, [$this, 'hasRole']);

        return count($roles) == count($myRoles);
    }
}
