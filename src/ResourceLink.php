<?php

namespace Franzl\Lti;

use DOMDocument;
use DOMElement;
use Exception;
use Franzl\Lti\Action\Action;
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
     * Context ID as supplied in the last connection request.
     *
     * @var string
     */
    public $lti_context_id = null;

    /**
     * Resource link ID as supplied in the last connection request.
     *
     * @var string
     */
    public $lti_resource_id = null;

    /**
     * Context title.
     *
     * @var string
     */
    public $title = null;

    /**
     * Setting values (LTI parameters, custom parameters and local parameters).
     *
     * @var array
     */
    public $settings = null;

    /**
     * User group sets (NULL if the consumer does not support the groups enhancement)
     *
     * @var array
     */
    public $groupSets = null;

    /**
     * User groups (NULL if the consumer does not support the groups enhancement)
     *
     * @var array
     */
    public $groups = null;

    /**
     * Consumer key value for resource link being shared (if any).
     *
     * @var string
     */
    public $primary_consumer_key = null;

    /**
     * ID value for resource link being shared (if any).
     *
     * @var string
     */
    public $primary_resource_link_id = null;

    /**
     * Whether the sharing request has been approved by the primary resource link.
     *
     * @var boolean
     */
    public $share_approved = null;

    /**
     * Date/time when the object was created.
     *
     * @var object
     */
    public $created = null;

    /**
     * Date/time when the object was last updated.
     *
     * @var object
     */
    public $updated = null;

    /**
     * Tool Consumer for this resource link.
     *
     * @var ToolConsumer
     */
    private $consumer = null;

    /**
     * ID for this resource link.
     *
     * @var string
     */
    private $id = null;

    /**
     * Previous ID for this resource link.
     *
     * @var string
     */
    private $previousId = null;

    /**
     * Whether the settings value have changed since last saved.
     *
     * @var boolean
     */
    private $settingsChanged = false;

    /**
     * XML node array for the last extension service request.
     *
     * @var array
     */
    private $extNodes = null;

    /**
     * Class constructor.
     *
     * @param string $consumer         Consumer key value
     * @param string $id               Resource link ID value
     * @param string $currentId        Current ID of resource link (optional, default is NULL)
     */
    public function __construct($consumer, $id, $currentId = null)
    {
        $this->consumer = $consumer;
        $this->id = $id;
        $this->previousId = $this->id;
        if (!empty($id)) {
            $this->load();
            if (is_null($this->created) && !empty($currentId)) {
                $this->id = $currentId;
                $this->load();
                $this->id = $id;
                $this->previousId = $currentId;
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
        $this->groupSets = null;
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
        $ok = $this->consumer->getStorage()->resourceLinkSave($this);
        if ($ok) {
            $this->settingsChanged = false;
        }

        return $ok;
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
        return $previous ? $this->previousId : $this->id;
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
        return isset($this->settings[$name]) ? $this->settings[$name] : $default;
    }

    /**
     * Set a setting value.
     *
     * @param string $name  Name of setting
     * @param string $value Value to set, use an empty value to delete a setting (optional, default is null)
     */
    public function setSetting($name, $value = null)
    {
        $oldValue = $this->getSetting($name);
        if ($value != $oldValue) {
            if (!empty($value)) {
                $this->settings[$name] = $value;
            } else {
                unset($this->settings[$name]);
            }
            $this->settingsChanged = true;
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
        return !$this->settingsChanged || $this->save();
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
     * @param Action $action The action type constant
     * @param User $user User object
     * @return bool True if the request was successfully processed
     */
    public function doOutcomesService(Action $action, User $user)
    {
        // Lookup service details from the source resource link appropriate to the user (in case the destination is being shared)
        $source_resource_link = $user->getResourceLink();
        $url = $source_resource_link->getSetting('lis_outcome_service_url');

        if ($this->doLTI11Service($action, $url)) {
            return $action->handleResponse($this->extNodes, $this);
        }

        return false;
    }

    /**
     * Perform a Memberships service request.
     *
     * The user table is updated with the new list of user objects.
     *
     * @param Action $action
     * @return mixed Array of User objects or False if the request was not successful
     *
     */
    public function doMembershipsService(Action $action)
    {
        $users = [];
        $url = $this->getSetting('ext_ims_lis_memberships_url');
        $params = [
            'id' => $this->getSetting('ext_ims_lis_memberships_id')
        ];

        $ok = $this->doService($action, $url, $params);


        return $users;
    }

    /**
     * Perform a Setting service request.
     *
     * @param Action $action The action
     * @param string $value  The setting value (optional, default is null)
     *
     * @return mixed The setting value for a read action, true if a write or delete action was successful, otherwise false
     */
    public function doSettingService(Action $action, $value = null)
    {
        $response = false;

        $name = $action->getServiceName();

        $url = $this->getSetting('ext_ims_lti_tool_setting_url');
        $params = [
            'id' => $this->getSetting('ext_ims_lti_tool_setting_id'),
            'setting' => $value ?: '',
        ];

        if ($this->doService($name, $url, $params)) {
            $response = $action->handleResponse($this->extNodes, $this);
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
        return $this->consumer->getStorage()->resourceLinkGetUserResultSourcedIDs($this, $local_only, $id_scope);
    }

    /**
     * Get an array of ResourceLinkShare objects for each resource link which is sharing this context.
     *
     * @return array Array of ResourceLinkShare objects
     */
    public function getShares()
    {
        return $this->consumer->getStorage()->resourceLinkGetShares($this);
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
        $params = $this->consumer->signParameters($url, $type, $this->consumer->ltiVersion, $params);

        // Connect to tool consumer
        $response = ClientFactory::make()->send($url, 'POST', $params);

        // Parse XML response
        if ($response->isSuccessful()) {
            $response = $response->getWrappedResponse();
            try {
                $extDoc = new DOMDocument();
                $extDoc->loadXML((string) $response->getBody());
                $this->extNodes = $this->domNodeToArray($extDoc->documentElement);

                $codeMajor = 'statusinfo,codemajor';
                return array_get($this->extNodes, $codeMajor) == 'Success';
            } catch (Exception $e) {
                // Pass
            }
        }

        return false;
    }

    /**
     * Send a service request to the tool consumer.
     *
     * @param Action $action
     * @param string $url URL to send request to
     * @return bool True if the request successfully obtained a response
     */
    private function doLTI11Service(Action $action, $url)
    {
        $xmlRequest = $action->getBody();

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
                $extDoc = new DOMDocument();
                $extDoc->loadXML((string) $response->getBody());
                $this->extNodes = $this->domNodeToArray($extDoc->documentElement);

                $codeMajor = 'imsx_POXHeader.imsx_POXResponseHeaderInfo.imsx_statusInfo.imsx_codeMajor';
                return array_get($this->extNodes, $codeMajor) == 'success';
            } catch (Exception $e) {
                // Pass
            }
        }

        return false;
    }

    protected function doServiceCall(Action $action, $url)
    {
        $httpClient = ClientFactory::make();
        $body = $action->getBody();
        $headers = [
            'content-type' => $action->getContentType(),
        ];

        $call = $httpClient->sendSigned($url, 'POST', $body, $headers);

        if ($call->isSuccessful()) {
            $response = (string) $call->getWrappedResponse()->getBody();
            try {
                $extDoc = new DOMDocument();
                $extDoc->loadXML($response);
                $action->handleResponse($this->domNodeToArray($extDoc->documentElement), $this);
            } catch (Exception $e) {
                // Pass
            }
        }
    }

    /**
     * Convert DOM nodes to array.
     *
     * @param DOMElement $node XML element
     *
     * @return array Array of XML document elements
     */
    private function domNodeToArray(DOMElement $node)
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
