<?php

namespace Franzl\Lti;

use DOMDocument;
use Exception;
use Franzl\Lti\Http\ClientFactory;
use Franzl\Lti\OAuth\Consumer;

/**
 * Class to represent a tool consumer resource link
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceLink
{

    /**
     * Read action.
     */
    const EXT_READ = 1;
    /**
     * Write (create/update) action.
     */
    const EXT_WRITE = 2;
    /**
     * Delete action.
     */
    const EXT_DELETE = 3;

    /**
     * Decimal outcome type.
     */
    const EXT_TYPE_DECIMAL = 'decimal';
    /**
     * Percentage outcome type.
     */
    const EXT_TYPE_PERCENTAGE = 'percentage';
    /**
     * Ratio outcome type.
     */
    const EXT_TYPE_RATIO = 'ratio';
    /**
     * Letter (A-F) outcome type.
     */
    const EXT_TYPE_LETTER_AF = 'letteraf';
    /**
     * Letter (A-F) with optional +/- outcome type.
     */
    const EXT_TYPE_LETTER_AF_PLUS = 'letterafplus';
    /**
     * Pass/fail outcome type.
     */
    const EXT_TYPE_PASS_FAIL = 'passfail';
    /**
     * Free text outcome type.
     */
    const EXT_TYPE_TEXT = 'freetext';

    /**
     * @var string Context ID as supplied in the last connection request.
     */
    public $lti_context_id = null;
    /**
     * @var string Resource link ID as supplied in the last connection request.
     */
    public $lti_resource_id = null;
    /**
     * @var string Context title.
     */
    public $title = null;
    /**
     * @var array Setting values (LTI parameters, custom parameters and local parameters).
     */
    public $settings = null;
    /**
     * @var array User group sets (NULL if the consumer does not support the groups enhancement)
     */
    public $group_sets = null;
    /**
     * @var array User groups (NULL if the consumer does not support the groups enhancement)
     */
    public $groups = null;
    /**
     * @var string Consumer key value for resource link being shared (if any).
     */
    public $primary_consumer_key = null;
    /**
     * @var string ID value for resource link being shared (if any).
     */
    public $primary_resource_link_id = null;
    /**
     * @var boolean Whether the sharing request has been approved by the primary resource link.
     */
    public $share_approved = null;
    /**
     * @var object Date/time when the object was created.
     */
    public $created = null;
    /**
     * @var object Date/time when the object was last updated.
     */
    public $updated = null;

    /**
     * @var ToolConsumer Tool Consumer for this resource link.
     */
    private $consumer = null;
    /**
     * @var string ID for this resource link.
     */
    private $id = null;
    /**
     * @var string Previous ID for this resource link.
     */
    private $previous_id = null;
    /**
     * @var boolean Whether the settings value have changed since last saved.
     */
    private $settings_changed = false;
    /**
     * @var string XML document for the last extension service request.
     */
    private $ext_doc = null;
    /**
     * @var array XML node array for the last extension service request.
     */
    private $ext_nodes = null;

    /**
     * Class constructor.
     *
     * @param string $consumer         Consumer key value
     * @param string $id               Resource link ID value
     * @param string $current_id       Current ID of resource link (optional, default is NULL)
     */
    public function __construct($consumer, $id, $current_id = null)
    {
        $this->consumer = $consumer;
        $this->id = $id;
        $this->previous_id = $this->id;
        if (!empty($id)) {
            $this->load();
            if (is_null($this->created) && !empty($current_id)) {
                $this->id = $current_id;
                $this->load();
                $this->id = $id;
                $this->previous_id = $current_id;
            }
        } else {
            $this->initialise();
        }
    }

    /**
     * Initialise the resource link.
     */
    public function initialise()
    {
        $this->lti_context_id = null;
        $this->lti_resource_id = null;
        $this->title = '';
        $this->settings = [];
        $this->group_sets = null;
        $this->groups = null;
        $this->primary_consumer_key = null;
        $this->primary_resource_link_id = null;
        $this->share_approved = null;
        $this->created = null;
        $this->updated = null;
    }

    /**
     * Save the resource link to the database.
     *
     * @return boolean True if the resource link was successfully saved.
     */
    public function save()
    {
        $ok = $this->consumer->getStorage()->Resource_Link_save($this);
        if ($ok) {
            $this->settings_changed = false;
        }

        return $ok;
    }

    /**
     * Delete the resource link from the database.
     *
     * @return boolean True if the resource link was successfully deleted.
     */
    public function delete()
    {
        return $this->consumer->getStorage()->Resource_Link_delete($this);
    }

    /**
     * Get tool consumer.
     *
     * @return object ToolConsumer object for this resource link.
     */
    public function getConsumer()
    {
        return $this->consumer;
    }

    /**
     * Get tool consumer key.
     *
     * @return string Consumer key value for this resource link.
     */
    public function getKey()
    {
        return $this->consumer->getKey();
    }

    /**
     * Get resource link ID.
     *
     * @param string $previous   TRUE if previous ID value is to be returned (optional, default is FALSE)
     *
     * @return string ID for this resource link.
     */
    public function getId($previous = false)
    {
        if ($previous) {
            $id = $this->previous_id;
        } else {
            $id = $this->id;
        }

        return $id;
    }

    /**
     * Get a setting value.
     *
     * @param string $name    Name of setting
     * @param string $default Value to return if the setting does not exist (optional, default is an empty string)
     *
     * @return string Setting value
     */
    public function getSetting($name, $default = '')
    {
        if (array_key_exists($name, $this->settings)) {
            $value = $this->settings[$name];
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Set a setting value.
     *
     * @param string $name  Name of setting
     * @param string $value Value to set, use an empty value to delete a setting (optional, default is null)
     */
    public function setSetting($name, $value = null)
    {
        $old_value = $this->getSetting($name);
        if ($value != $old_value) {
            if (!empty($value)) {
                $this->settings[$name] = $value;
            } else {
                unset($this->settings[$name]);
            }
            $this->settings_changed = true;
        }
    }

    /**
     * Get an array of all setting values.
     *
     * @return array Associative array of setting values
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Save setting values.
     *
     * @return boolean True if the settings were successfully saved
     */
    public function saveSettings()
    {
        if ($this->settings_changed) {
            $ok = $this->save();
        } else {
            $ok = true;
        }

        return $ok;
    }

    /**
     * Check if the Outcomes service is supported.
     *
     * @return boolean True if this resource link supports the Outcomes service (either the LTI 1.1 or extension service)
     */
    public function hasOutcomesService()
    {
        $url = $this->getSetting('ext_ims_lis_basic_outcome_url') . $this->getSetting('lis_outcome_service_url');

        return !empty($url);
    }

    /**
     * Check if the Memberships service is supported.
     *
     * @return boolean True if this resource link supports the Memberships service
     */
    public function hasMembershipsService()
    {
        $url = $this->getSetting('ext_ims_lis_memberships_url');

        return !empty($url);
    }

    /**
     * Check if the Setting service is supported.
     *
     * @return boolean True if this resource link supports the Setting service
     */
    public function hasSettingService()
    {
        $url = $this->getSetting('ext_ims_lti_tool_setting_url');

        return !empty($url);
    }

    /**
     * Perform an Outcomes service request.
     *
     * @param int $action The action type constant
     * @param Outcome $lti_outcome Outcome object
     * @param User $user User object
     *
     * @return boolean True if the request was successfully processed
     */
    public function doOutcomesService($action, $lti_outcome, $user = null)
    {
        $response = false;

        // Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        $source_resource_link = $this;
        $sourcedid = $lti_outcome->getSourcedid();
        if (!is_null($user)) {
            $source_resource_link = $user->getResourceLink();
            $sourcedid = $user->ltiResultSourcedId;
        }

        // Use LTI 1.1 service in preference to extension service if it is available
        $urlLTI11 = $source_resource_link->getSetting('lis_outcome_service_url');
        $urlExt = $source_resource_link->getSetting('ext_ims_lis_basic_outcome_url');
        if ($urlExt || $urlLTI11) {
            switch ($action) {
                case self::EXT_READ:
                    if ($urlLTI11 && ($lti_outcome->type == self::EXT_TYPE_DECIMAL)) {
                        $do = 'readResult';
                    } else if ($urlExt) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-readresult';
                    }
                    break;
                case self::EXT_WRITE:
                    if ($urlLTI11 && $this->checkValueType($lti_outcome, [self::EXT_TYPE_DECIMAL])) {
                        $do = 'replaceResult';
                    } else if ($this->checkValueType($lti_outcome)) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-updateresult';
                    }
                    break;
                case self::EXT_DELETE:
                    if ($urlLTI11 && ($lti_outcome->type == self::EXT_TYPE_DECIMAL)) {
                        $do = 'deleteResult';
                    } else if ($urlExt) {
                        $urlLTI11 = null;
                        $do = 'basic-lis-deleteresult';
                    }
                    break;
            }
        }
        if (isset($do)) {
            $value = $lti_outcome->getValue();
            if (is_null($value)) {
                $value = '';
            }
            if ($urlLTI11) {
                $xml = '';
                if ($action == self::EXT_WRITE) {
                    $xml = <<<EOF

        <result>
          <resultScore>
            <language>{$lti_outcome->language}</language>
            <textString>{$value}</textString>
          </resultScore>
        </result>
EOF;
                }
                $sourcedid = htmlentities($sourcedid);
                $xml = <<<EOF
      <resultRecord>
        <sourcedGUID>
          <sourcedId>{$sourcedid}</sourcedId>
        </sourcedGUID>{$xml}
      </resultRecord>
EOF;
                if ($this->doLTI11Service($do, $urlLTI11, $xml)) {
                    switch ($action) {
                        case self::EXT_READ:
                            if (!isset($this->ext_nodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString'])) {
                                break;
                            }

                            $lti_outcome->setValue($this->ext_nodes['imsx_POXBody']["{$do}Response"]['result']['resultScore']['textString']);
                            // no break
                        case self::EXT_WRITE:
                        case self::EXT_DELETE:
                            $response = true;
                            break;
                    }
                }
            } else {
                $params = [];
                $params['sourcedid'] = $sourcedid;
                $params['result_resultscore_textstring'] = $value;
                if (!empty($lti_outcome->language)) {
                    $params['result_resultscore_language'] = $lti_outcome->language;
                }
                if (!empty($lti_outcome->status)) {
                    $params['result_statusofresult'] = $lti_outcome->status;
                }
                if (!empty($lti_outcome->date)) {
                    $params['result_date'] = $lti_outcome->date;
                }
                if (!empty($lti_outcome->type)) {
                    $params['result_resultvaluesourcedid'] = $lti_outcome->type;
                }
                if (!empty($lti_outcome->data_source)) {
                    $params['result_datasource'] = $lti_outcome->data_source;
                }
                if ($this->doService($do, $urlExt, $params)) {
                    switch ($action) {
                        case self::EXT_READ:
                            if (isset($this->ext_nodes['result']['resultscore']['textstring'])) {
                                $response = $this->ext_nodes['result']['resultscore']['textstring'];
                            }
                            break;
                        case self::EXT_WRITE:
                        case self::EXT_DELETE:
                            $response = true;
                            break;
                    }
                }
            }
            if (is_array($response) && (count($response) <= 0)) {
                $response = '';
            }
        }

        return $response;
    }

    /**
     * Perform a Memberships service request.
     *
     * The user table is updated with the new list of user objects.
     *
     * @param boolean $withGroups True is group information is to be requested as well
     *
     * @return mixed Array of User objects or False if the request was not successful
     */
    public function doMembershipsService($withGroups = false)
    {
        $users = [];
        $old_users = $this->getUserResultSourcedIDs(true, ToolProvider::ID_SCOPE_RESOURCE);
        $url = $this->getSetting('ext_ims_lis_memberships_url');
        $params = [];
        $params['id'] = $this->getSetting('ext_ims_lis_memberships_id');
        $ok = false;
        if ($withGroups) {
            $ok = $this->doService('basic-lis-readmembershipsforcontextwithgroups', $url, $params);
        }
        if ($ok) {
            $this->group_sets = [];
            $this->groups = [];
        } else {
            $ok = $this->doService('basic-lis-readmembershipsforcontext', $url, $params);
        }

        if ($ok) {
            if (!isset($this->ext_nodes['memberships']['member'])) {
                $members = [];
            } else if (!isset($this->ext_nodes['memberships']['member'][0])) {
                $members = [];
                $members[0] = $this->ext_nodes['memberships']['member'];
            } else {
                $members = $this->ext_nodes['memberships']['member'];
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
                        if (!isset($this->group_sets[$set_id])) {
                            $this->group_sets[$set_id] = ['title' => $group['set']['title'], 'groups' => [],
                                                               'num_members' => 0, 'num_staff' => 0, 'num_learners' => 0];
                        }
                        $this->group_sets[$set_id]['num_members']++;
                        if ($user->isStaff()) {
                            $this->group_sets[$set_id]['num_staff']++;
                        }
                        if ($user->isLearner()) {
                            $this->group_sets[$set_id]['num_learners']++;
                        }
                        if (!in_array($group['id'], $this->group_sets[$set_id]['groups'])) {
                            $this->group_sets[$set_id]['groups'][] = $group['id'];
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
                unset($old_users[$user->getId(ToolProvider::ID_SCOPE_RESOURCE)]);
            }

            // Delete any old users which were not in the latest list from the tool consumer
            foreach ($old_users as $id => $user) {
                $user->delete();
            }
        } else {
            $users = false;
        }

        return $users;
    }

    /**
     * Perform a Setting service request.
     *
     * @param int    $action The action type constant
     * @param string $value  The setting value (optional, default is null)
     *
     * @return mixed The setting value for a read action, true if a write or delete action was successful, otherwise false
     */
    public function doSettingService($action, $value = null)
    {
        $response = false;
        switch ($action) {
            case self::EXT_READ:
                $do = 'basic-lti-loadsetting';
                break;
            case self::EXT_WRITE:
                $do = 'basic-lti-savesetting';
                break;
            case self::EXT_DELETE:
                $do = 'basic-lti-deletesetting';
                break;
        }
        if (isset($do)) {
            $url = $this->getSetting('ext_ims_lti_tool_setting_url');
            $params = [];
            $params['id'] = $this->getSetting('ext_ims_lti_tool_setting_id');
            if (is_null($value)) {
                $value = '';
            }
            $params['setting'] = $value;

            if ($this->doService($do, $url, $params)) {
                switch ($action) {
                    case self::EXT_READ:
                        if (isset($this->ext_nodes['setting']['value'])) {
                            $response = $this->ext_nodes['setting']['value'];
                            if (is_array($response)) {
                                $response = '';
                            }
                        }
                        break;
                    case self::EXT_WRITE:
                        $this->setSetting('ext_ims_lti_tool_setting', $value);
                        $this->saveSettings();
                        $response = true;
                        break;
                    case self::EXT_DELETE:
                        $response = true;
                        break;
                }
            }

        }

        return $response;
    }

    /**
     * Obtain an array of User objects for users with a result sourcedId.
     *
     * The array may include users from other resource links which are sharing this resource link.
     * It may also be optionally indexed by the user ID of a specified scope.
     *
     * @param boolean $local_only True if only users from this resource link are to be returned, not users from shared resource links (optional, default is false)
     * @param int     $id_scope     Scope to use for ID values (optional, default is null for consumer default)
     *
     * @return array Array of User objects
     */
    public function getUserResultSourcedIDs($local_only = false, $id_scope = null)
    {
        return $this->consumer->getStorage()->Resource_Link_getUserResultSourcedIDs($this, $local_only, $id_scope);
    }

    /**
     * Get an array of ResourceLinkShare objects for each resource link which is sharing this context.
     *
     * @return array Array of ResourceLinkShare objects
     */
    public function getShares()
    {
        return $this->consumer->getStorage()->Resource_Link_getShares($this);
    }

    /**
     * Load the resource link from the database.
     *
     * @return boolean True if resource link was successfully loaded
     */
    private function load()
    {
        $this->initialise();
        return $this->consumer->getStorage()->resourceLinkLoad($this);
    }

    /**
     * Convert data type of value to a supported type if possible.
     *
     * @param Outcome $lti_outcome     Outcome object
     * @param string[]    $supported_types Array of outcome types to be supported (optional, default is null to use supported types reported in the last launch for this resource link)
     *
     * @return boolean True if the type/value are valid and supported
     */
    private function checkValueType($lti_outcome, $supported_types = null)
    {
        if (empty($supported_types)) {
            $supported_types = explode(',', str_replace(' ', '', strtolower($this->getSetting('ext_ims_lis_resultvalue_sourcedids', self::EXT_TYPE_DECIMAL))));
        }
        $type = $lti_outcome->type;
        $value = $lti_outcome->getValue();

        // Check whether the type is supported or there is no value
        $ok = in_array($type, $supported_types) || (strlen($value) <= 0);

        if (!$ok) {
            // Convert numeric values to decimal
            if ($type == self::EXT_TYPE_PERCENTAGE) {
                if (substr($value, -1) == '%') {
                    $value = substr($value, 0, -1);
                }
                $ok = is_numeric($value) && ($value >= 0) && ($value <= 100);
                if ($ok) {
                    $lti_outcome->setValue($value / 100);
                    $lti_outcome->type = self::EXT_TYPE_DECIMAL;
                }
            } else if ($type == self::EXT_TYPE_RATIO) {
                $parts = explode('/', $value, 2);
                $ok = (count($parts) == 2) && is_numeric($parts[0]) && is_numeric($parts[1]) && ($parts[0] >= 0) && ($parts[1] > 0);
                if ($ok) {
                    $lti_outcome->setValue($parts[0] / $parts[1]);
                    $lti_outcome->type = self::EXT_TYPE_DECIMAL;
                }
            } else if ($type == self::EXT_TYPE_LETTER_AF) {
                // Convert letter_af to letter_af_plus or text
                if (in_array(self::EXT_TYPE_LETTER_AF_PLUS, $supported_types)) {
                    $ok = true;
                    $lti_outcome->type = self::EXT_TYPE_LETTER_AF_PLUS;
                } else if (in_array(self::EXT_TYPE_TEXT, $supported_types)) {
                    $ok = true;
                    $lti_outcome->type = self::EXT_TYPE_TEXT;
                }
            } else if ($type == self::EXT_TYPE_LETTER_AF_PLUS) {
                // Convert letter_af_plus to letter_af or text
                if (in_array(self::EXT_TYPE_LETTER_AF, $supported_types) && (strlen($value) == 1)) {
                    $ok = true;
                    $lti_outcome->type = self::EXT_TYPE_LETTER_AF;
                } else if (in_array(self::EXT_TYPE_TEXT, $supported_types)) {
                    $ok = true;
                    $lti_outcome->type = self::EXT_TYPE_TEXT;
                }
            } else if ($type == self::EXT_TYPE_TEXT) {
                // Convert text to decimal
                $ok = is_numeric($value) && ($value >= 0) && ($value <=1);
                if ($ok) {
                    $lti_outcome->type = self::EXT_TYPE_DECIMAL;
                } else if (substr($value, -1) == '%') {
                    $value = substr($value, 0, -1);
                    $ok = is_numeric($value) && ($value >= 0) && ($value <=100);
                    if ($ok) {
                        if (in_array(self::EXT_TYPE_PERCENTAGE, $supported_types)) {
                            $lti_outcome->type = self::EXT_TYPE_PERCENTAGE;
                        } else {
                            $lti_outcome->setValue($value / 100);
                            $lti_outcome->type = self::EXT_TYPE_DECIMAL;
                        }
                    }
                }
            }
        }

        return $ok;
    }

    /**
     * Send a service request to the tool consumer.
     *
     * @param string $type   GuzzleClient type value
     * @param string $url    URL to send request to
     * @param array  $params Associative array of parameter values to be passed
     *
     * @return boolean True if the request successfully obtained a response
     */
    private function doService($type, $url, $params)
    {
        $ok = false;
        if (!empty($url)) {
            $params = $this->consumer->signParameters($url, $type, $this->consumer->ltiVersion, $params);

            // Connect to tool consumer
            $response = ClientFactory::make()->send($url, 'POST', $params);

            // Parse XML response
            if ($response->isSuccessful()) {
                $response = $response->getWrappedResponse();
                try {
                    $this->ext_doc = new DOMDocument();
                    $this->ext_doc->loadXML((string) $response->getBody());
                    $this->ext_nodes = $this->domNodeToArray($this->ext_doc->documentElement);
                    if (isset($this->ext_nodes['statusinfo']['codemajor']) && ($this->ext_nodes['statusinfo']['codemajor'] == 'Success')) {
                        $ok = true;
                    }
                } catch (Exception $e) {
                }
            }
        }

        return $ok;
    }

    /**
     * Send a service request to the tool consumer.
     *
     * @param string $type GuzzleClient type value
     * @param string $url  URL to send request to
     * @param string $xml  XML of message request
     *
     * @return boolean True if the request successfully obtained a response
     */
    private function doLTI11Service($type, $url, $xml)
    {
        $ok = false;
        if (!empty($url)) {
            $id = uniqid();
            $xmlRequest = <<< EOD
<?xml version = "1.0" encoding = "UTF-8"?>
<imsx_POXEnvelopeRequest xmlns = "http://www.imsglobal.org/services/ltiv1p1/xsd/imsoms_v1p0">
  <imsx_POXHeader>
    <imsx_POXRequestHeaderInfo>
      <imsx_version>V1.0</imsx_version>
      <imsx_messageIdentifier>{$id}</imsx_messageIdentifier>
    </imsx_POXRequestHeaderInfo>
  </imsx_POXHeader>
  <imsx_POXBody>
    <{$type}Request>
{$xml}
    </{$type}Request>
  </imsx_POXBody>
</imsx_POXEnvelopeRequest>
EOD;
            // Calculate body hash
            $hash = base64_encode(sha1($xmlRequest, true));
            $params = ['oauth_body_hash' => $hash];

            // Add OAuth signature
            $consumer = new Consumer($this->consumer->getKey(), $this->consumer->secret, null);

            // Connect to tool consumer
            $response = ClientFactory::make()->sendSigned($url, 'POST', $xmlRequest, [
                'content-type' => 'application/xml',
            ]);

            // Parse XML response
            if ($response->isSuccessful()) {
                $response = $response->getWrappedResponse();
                try {
                    $this->ext_doc = new DOMDocument();
                    $this->ext_doc->loadXML((string) $response->getBody());
                    $this->ext_nodes = $this->domNodeToArray($this->ext_doc->documentElement);
                    if (isset($this->ext_nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor']) &&
                        ($this->ext_nodes['imsx_POXHeader']['imsx_POXResponseHeaderInfo']['imsx_statusInfo']['imsx_codeMajor'] == 'success')) {
                        $ok = true;
                    }
                } catch (Exception $e) {
                }
            }
        }

        return $ok;
    }

    /**
     * Convert DOM nodes to array.
     *
     * @param DOMElement $node XML element
     *
     * @return array Array of XML document elements
     */
    private function domNodeToArray($node)
    {
        $output = '';
        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                $output = trim($node->textContent);
                break;
            case XML_ELEMENT_NODE:
                for ($i = 0; $i < $node->childNodes->length; $i++) {
                    $child = $node->childNodes->item($i);
                    $v = $this->domNodeToArray($child);
                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($output[$t])) {
                            $output[$t] = [];
                        }
                        $output[$t][] = $v;
                    } else {
                        $s = (string) $v;
                        if (strlen($s) > 0) {
                            $output = $s;
                        }
                    }
                }
                if (is_array($output)) {
                    if ($node->attributes->length) {
                        $a = [];
                        foreach ($node->attributes as $attrName => $attrNode) {
                            $a[$attrName] = (string) $attrNode->value;
                        }
                        $output['@attributes'] = $a;
                    }
                    foreach ($output as $t => $v) {
                        if (is_array($v) && count($v)==1 && $t!='@attributes') {
                            $output[$t] = $v[0];
                        }
                    }
                }
                break;
        }

        return $output;
    }
}
