<?php

namespace Franzl\Lti;

use Exception;
use Franzl\Lti\OAuth\DataStore;
use Franzl\Lti\OAuth\Request;
use Franzl\Lti\OAuth\Server;
use Franzl\Lti\OAuth\SignatureMethodHmacSha1;
use Franzl\Lti\Storage\AbstractStorage;

/**
 * Class to represent an LTI Tool Provider
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ToolProvider
{
    /**
     * Default connection error message.
     */
    const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    /**
     * LTI version 1 for messages.
     */
    const LTI_VERSION1 = 'LTI-1p0';

    /**
     * LTI version 2 for messages.
     */
    const LTI_VERSION2 = 'LTI-2p0';

    /**
     * Use ID value only.
     */
    const ID_SCOPE_ID_ONLY = 0;

    /**
     * Prefix an ID with the consumer key.
     */
    const ID_SCOPE_GLOBAL = 1;

    /**
     * Prefix the ID with the consumer key and context ID.
     */
    const ID_SCOPE_CONTEXT = 2;

    /**
     * Prefix the ID with the consumer key and resource ID.
     */
    const ID_SCOPE_RESOURCE = 3;

    /**
     * Character used to separate each element of an ID.
     */
    const ID_SCOPE_SEPARATOR = ':';

    /**
     * @var boolean True if the last request was successful.
     */
    public $isOK = true;

    /**
     * @var ToolConsumer Tool Consumer object.
     */
    public $consumer = null;

    /**
     * @var string Return URL provided by tool consumer.
     */
    public $return_url = null;

    /**
     * @var User User object.
     */
    public $user = null;

    /**
     * @var ResourceLink Resource link object.
     */
    public $resource_link = null;

    /**
     * @var AbstractStorage Data connector object.
     */
    public $data_connector = null;

    /**
     * @var string Default email domain.
     */
    public $defaultEmail = '';

    /**
     * @var int Scope to use for user IDs.
     */
    public $id_scope = self::ID_SCOPE_ID_ONLY;

    /**
     * @var boolean Whether shared resource link arrangements are permitted.
     */
    public $allowSharing = false;

    /**
     * @var string Message for last request processed
     */
    public $message = self::CONNECTION_ERROR_MESSAGE;

    /**
     * @var string Error message for last request processed.
     */
    public $reason = null;

    /**
     * @var array Details for error message relating to last request processed.
     */
    public $details = [];

    /**
     * @var string URL to redirect user to on successful completion of the request.
     */
    protected $redirectURL = null;

    /**
     * @var string URL to redirect user to on successful completion of the request.
     */
    protected $mediaTypes = null;

    /**
     * @var string URL to redirect user to on successful completion of the request.
     */
    protected $documentTargets = null;

    /**
     * @var string HTML to be displayed on a successful completion of the request.
     */
    protected $output = null;

    /**
     * @var string HTML to be displayed on an unsuccessful completion of the request and no return URL is available.
     */
    protected $error_output = null;

    /**
     * @var boolean Whether debug messages explaining the cause of errors are to be returned to the tool consumer.
     */
    protected $debugMode = false;

    /**
     * @var array Callback functions for handling requests.
     */
    private $callbackHandler = null;

    /**
     * @var array LTI parameter constraints for auto validation checks.
     */
    private $constraints = null;

    /**
     * @var array List of supported message types and associated callback type names
     */
    private $messageTypes = ['basic-lti-launch-request'    => 'launch',
                             'ConfigureLaunchRequest'      => 'configure',
                             'DashboardRequest'            => 'dashboard',
                             'ContentItemSelectionRequest' => 'content-item'];

    /**
     * @var array List of supported message types and associated class methods
     */
    private $methodNames = ['basic-lti-launch-request'    => 'onLaunch',
                            'ConfigureLaunchRequest'      => 'onConfigure',
                            'DashboardRequest'            => 'onDashboard',
                            'ContentItemSelectionRequest' => 'onContentItem'];

    /**
     * @var array Names of LTI parameters to be retained in the settings property.
     */
    private $lti_settings_names = ['ext_resource_link_content', 'ext_resource_link_content_signature',
        'lis_result_sourcedid', 'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url', 'ext_ims_lis_resultvalue_sourcedids',
        'ext_ims_lis_memberships_id', 'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting', 'ext_ims_lti_tool_setting_id', 'ext_ims_lti_tool_setting_url'];

    /**
     * @var array Permitted LTI versions for messages.
     */
    private $LTI_VERSIONS = [self::LTI_VERSION1, self::LTI_VERSION2];

    /**
     * Class constructor
     *
     * @param mixed $data_connector Object containing a database connection object (optional, default is a blank prefix and MySQL)
     * @param mixed $callbackHandler String containing name of callback function for launch request, or associative array of callback functions for each request type
     */
    public function __construct($data_connector = '', $callbackHandler = null)
    {
        $this->constraints = [];
        $this->callbackHandler = [];
        if (is_array($callbackHandler)) {
            $this->callbackHandler = $callbackHandler;
            if (isset($this->callbackHandler['connect']) && !isset($this->callbackHandler['launch'])) {  // for backward compatibility
                $this->callbackHandler['launch'] = $this->callbackHandler['connect'];
                unset($this->callbackHandler['connect']);
            }
        } else if (!empty($callbackHandler)) {
            $this->callbackHandler['launch'] = $callbackHandler;
        }
        $this->data_connector = AbstractStorage::getDataConnector($data_connector);
        $this->isOK = !is_null($this->data_connector);

        // Set debug mode
        $this->debugMode = isset($_POST['custom_debug']) && (strtolower($_POST['custom_debug']) == 'true');

        // Set return URL if available
        if (isset($_POST['launch_presentation_return_url'])) {
            $this->return_url = $_POST['launch_presentation_return_url'];
        } else if (isset($_POST['content_item_return_url'])) {
            $this->return_url = $_POST['content_item_return_url'];
        }
    }

    /**
     * Process an incoming request
     *
     * @return mixed Returns TRUE or FALSE, a redirection URL or HTML
     */
    public function handleRequest()
    {
        // Perform action
        if ($this->isOK) {
            if ($this->authenticate()) {
                $this->doCallback();
            }
        }
        $this->result();
    }

    /**
     * Add a parameter constraint to be checked on launch
     *
     * @param string $name Name of parameter to be checked
     * @param boolean $required True if parameter is required (optional, default is TRUE)
     * @param int $max_length Maximum permitted length of parameter value (optional, default is NULL)
     * @param array $message_types Array of message types to which the constraint applies (default is all)
     */
    public function setParameterConstraint($name, $required = true, $max_length = null, $message_types = null)
    {
        $name = trim($name);
        if (strlen($name) > 0) {
            $this->constraints[$name] = ['required' => $required, 'max_length' => $max_length, 'messages' => $message_types];
        }
    }

    /**
     * Get an array of defined tool consumers
     *
     * @return array Array of ToolConsumer objects
     */
    public function getConsumers()
    {
        // Initialise data connector
        $this->data_connector = AbstractStorage::getDataConnector($this->data_connector);

        return $this->data_connector->toolConsumerList();
    }

    /**
     * Get an array of fully qualified user roles
     *
     * @param string Comma-separated list of roles
     *
     * @return array Array of roles
     */
    public static function parseRoles($rolesString)
    {
        $rolesArray = explode(',', $rolesString);
        $roles = [];
        foreach ($rolesArray as $role) {
            $role = trim($role);
            if (!empty($role)) {
                if (substr($role, 0, 4) != 'urn:') {
                    $role = 'urn:lti:role:ims/lis/' . $role;
                }
                $roles[] = $role;
            }
        }

        return $roles;
    }

    /**
     * Generate a web page containing an auto-submitted form of parameters.
     *
     * @param string $url URL to which the form should be submitted
     * @param array $params Array of form parameters
     * @param string $target Name of target (optional)
     * @return string
     */
    public static function sendForm($url, $params, $target = '')
    {
        $page = <<< EOD
<html>
<head>
<title>IMS LTI message</title>
<script type="text/javascript">
//<![CDATA[
function doOnLoad() {
  document.forms[0].submit();
}

window.onload=doOnLoad;
//]]>
</script>
</head>
<body>
<form action="{$url}" method="post" target="" encType="application/x-www-form-urlencoded">

EOD;

        foreach ($params as $key => $value) {
            $key = htmlentities($key, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $value = htmlentities($value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
            $page .= <<< EOD
  <input type="hidden" name="{$key}" value="{$value}" />

EOD;

        }

        $page .= <<< EOD
</form>
</body>
</html>
EOD;

        return $page;
    }

    /**
     * Process a valid launch request
     *
     * @return boolean True if no error
     */
    protected function onLaunch()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid configure request
     *
     * @return boolean True if no error
     */
    protected function onConfigure()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid dashboard request
     *
     * @return boolean True if no error
     */
    protected function onDashboard()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid content-item request
     *
     * @return boolean True if no error
     */
    protected function onContentItem()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a response to an invalid request
     *
     * @return boolean True if no further error processing required
     */
    protected function onError()
    {
        $this->doCallbackMethod('error');
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirectURL and output properties.
     *
     * @return boolean True if no error reported
     */
    private function doCallback()
    {
        $method = $this->methodNames[$_POST['lti_message_type']];
        $this->$method();
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirectURL and output properties.
     *
     * @param string $type Callback type
     *
     * @return boolean True if no error reported
     * @throws Exception
     */
    private function doCallbackMethod($type = null)
    {
        $callback = $type;
        if (is_null($callback)) {
            $callback = $this->messageTypes[$_POST['lti_message_type']];
        }
        if (isset($this->callbackHandler[$callback])) {
            $result = call_user_func($this->callbackHandler[$callback], $this);

            // Callback function may return HTML, a redirect URL, or a boolean value
            if (is_string($result)) {
                if ((substr($result, 0, 7) == 'http://') || (substr($result, 0, 8) == 'https://')) {
                    $this->redirectURL = $result;
                } else {
                    if (is_null($this->output)) {
                        $this->output = '';
                    }
                    $this->output .= $result;
                }
            } else if (is_bool($result)) {
                $this->isOK = $result;
            }
        } else if (is_null($type)) {
            throw new Exception('Message type not supported');
        }
    }

    /**
     * Perform the result of an action.
     *
     * This function may redirect the user to another URL rather than returning a value.
     *
     * @return string Output to be displayed (redirection, or display HTML or message)
     */
    private function result()
    {
        $ok = false;
        if (!$this->isOK) {
            $ok = $this->onError();
        }
        if (!$ok) {
            if (!$this->isOK) {
                // If not valid, return an error message to the tool consumer if a return URL is provided
                if (!empty($this->return_url)) {
                    $error_url = $this->return_url;
                    if (strpos($error_url, '?') === false) {
                        $error_url .= '?';
                    } else {
                        $error_url .= '&';
                    }
                    if ($this->debugMode && !is_null($this->reason)) {
                        $error_url .= 'lti_errormsg=' . urlencode("Debug error: $this->reason");
                    } else {
                        $error_url .= 'lti_errormsg=' . urlencode($this->message);
                        if (!is_null($this->reason)) {
                            $error_url .= '&lti_errorlog=' . urlencode("Debug error: $this->reason");
                        }
                    }
                    if (!is_null($this->consumer) && isset($_POST['lti_message_type']) && ($_POST['lti_message_type'] === 'ContentItemSelectionRequest')) {
                        $form_params = [];
                        if (isset($_POST['data'])) {
                            $form_params['data'] = $_POST['data'];
                        }
                        $version = (isset($_POST['lti_version'])) ? $_POST['lti_version'] : ToolProvider::LTI_VERSION1;
                        $form_params = $this->consumer->signParameters($error_url, 'ContentItemSelection', $version, $form_params);
                        $page = ToolProvider::sendForm($error_url, $form_params);
                        echo $page;
                    } else {
                        header("Location: {$error_url}");
                    }
                    exit;
                } else {
                    if (!is_null($this->error_output)) {
                        echo $this->error_output;
                    } else if ($this->debugMode && !empty($this->reason)) {
                        echo "Debug error: {$this->reason}";
                    } else {
                        echo "Error: {$this->message}";
                    }
                }
            } else if (!is_null($this->redirectURL)) {
                header("Location: {$this->redirectURL}");
                exit;
            } else if (!is_null($this->output)) {
                echo $this->output;
            }
        }
    }

    /**
     * Check the authenticity of the LTI launch request.
     *
     * The consumer, resource link and user objects will be initialised if the request is valid.
     *
     * @return boolean True if the request has been successfully validated.
     * @throws Exception
     */
    private function authenticate()
    {
        // Get the consumer
        $doSaveConsumer = false;

        // Check all required launch parameters
        $version = isset($_POST['lti_version']) ? $_POST['lti_version'] : '';
        $messageType = isset($_POST['lti_message_type']) ? $_POST['lti_message_type'] : '';

        if (!in_array($version, $this->LTI_VERSIONS)) {
            throw new Exception('Invalid or missing lti_version parameter');
        }

        switch ($messageType) {
            case 'basic-lti-launch-request':
            case 'DashboardRequest':
                if (!isset($_POST['resource_link_id']) || (strlen(trim($_POST['resource_link_id'])) == 0)) {
                    throw new Exception('Missing resource link ID');
                }
                break;
            case 'ContentItemSelectionRequest':
                $acceptMediaTypes = isset($_POST['accept_media_types']) ? trim($_POST['accept_media_types']) : '';
                if (strlen($acceptMediaTypes) == 0) {
                    throw new Exception('No accept_media_types found');
                }

                $mediaTypes = array_filter(explode(',', str_replace(' ', '', $acceptMediaTypes)), 'strlen');
                $mediaTypes = array_unique($mediaTypes);

                if (count($mediaTypes) == 0) {
                    throw new Exception('No valid accept_media_types found');
                }

                $this->mediaTypes = $mediaTypes;

                $acceptDocumentTargets = isset($_POST['accept_presentation_document_targets']) ? trim($_POST['accept_presentation_document_targets']) : '';
                if (strlen($acceptDocumentTargets) == 0) {
                    throw new Exception('No accept_presentation_document_targets found');
                }

                $documentTargets = array_filter(explode(',', str_replace(' ', '', $acceptDocumentTargets), 'strlen'));
                $documentTargets = array_unique($documentTargets);

                if (count($documentTargets) == 0) {
                    throw new Exception('No valid accept_presentation_document_targets found');
                }

                foreach ($documentTargets as $documentTarget) {
                    $this->checkValue(
                        $documentTarget,
                        ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay', 'none'],
                        'Invalid value in accept_presentation_document_targets parameter: %s.'
                    );
                }

                $this->documentTargets = $documentTargets;

                $returnUrl = isset($_POST['content_item_return_url']) ? trim($_POST['content_item_return_url']) : '';

                if (strlen($returnUrl) == 0) {
                    throw new Exception('Missing content_item_return_url parameter');
                }

                break;
            default:
                throw new Exception('Invalid or missing lti_message_type parameter');
        }

        // Check consumer key
        if (!isset($_POST['oauth_consumer_key'])) {
            throw new Exception('Missing consumer key');
        }

        $this->consumer = new ToolConsumer($_POST['oauth_consumer_key'], $this->data_connector);
        if (is_null($this->consumer->created)) {
            throw new Exception('Invalid consumer key');
        }

        $now = time();
        $today = date('Y-m-d', $now);
        if (is_null($this->consumer->last_access)) {
            $doSaveConsumer = true;
        } else {
            $last = date('Y-m-d', $this->consumer->last_access);
            $doSaveConsumer = $doSaveConsumer || ($last != $today);
        }
        $this->consumer->last_access = $now;

        $store = new DataStore($this);
        $server = new Server($store);
        $method = new SignatureMethodHmacSha1();
        $server->addSignatureMethod($method);
        $request = Request::fromRequest();
        $server->verifyRequest($request);

        if ($this->consumer->protected) {
            $consumerGuid = isset($_POST['tool_consumer_instance_guid']) ? $_POST['tool_consumer_instance_guid'] : '';

            if (empty($consumerGuid)) {
                throw new Exception('A tool consumer GUID must be included in the launch request');
            }

            if ($this->consumer->consumer_guid !== $consumerGuid) {
                throw new Exception('Request is from an invalid tool consumer');
            }
        }

        if (!$this->consumer->enabled) {
            throw new Exception('Tool consumer has not been enabled by the tool provider');
        }

        if (!is_null($this->consumer->enable_from) && ($this->consumer->enable_from > $now)) {
            throw new Exception('Tool consumer access is not yet available');
        }

        if (!is_null($this->consumer->enable_until) && ($this->consumer->enable_until <= $now)) {
            throw new Exception('Tool consumer access has expired');
        }

        // Validate other message parameter values
        if ($this->isOK) {
            if ($_POST['lti_message_type'] != 'ContentItemSelectionRequest') {
                if (isset($_POST['launch_presentation_document_target'])) {
                    $this->checkValue(
                        $_POST['launch_presentation_document_target'],
                        ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay'],
                        'Invalid value for launch_presentation_document_target parameter: %s.'
                    );
                }
            } else {
                if (isset($_POST['accept_unsigned'])) {
                    $this->checkValue($_POST['accept_unsigned'], ['true', 'false'], 'Invalid value for accept_unsigned parameter: %s.');
                }
                if (isset($_POST['accept_multiple'])) {
                    $this->checkValue($_POST['accept_multiple'], ['true', 'false'], 'Invalid value for accept_multiple parameter: %s.');
                }
                if (isset($_POST['accept_copy_advice'])) {
                    $this->checkValue($_POST['accept_copy_advice'], ['true', 'false'], 'Invalid value for accept_copy_advice parameter: %s.');
                }
                if (isset($_POST['auto_create'])) {
                    $this->checkValue($_POST['auto_create'], ['true', 'false'], 'Invalid value for auto_create parameter: %s.');
                }
                if (isset($_POST['can_confirm'])) {
                    $this->checkValue($_POST['can_confirm'], ['true', 'false'], 'Invalid value for can_confirm parameter: %s.');
                }
            }
        }

        // Validate message parameter constraints
        $invalid_parameters = [];
        foreach ($this->constraints as $name => $constraint) {
            if (empty($constraint['messages']) || in_array($messageType, $constraint['messages'])) {
                $ok = true;
                if ($constraint['required']) {
                    if (!isset($_POST[$name]) || (strlen(trim($_POST[$name])) <= 0)) {
                        $invalid_parameters[] = "{$name} (missing)";
                        $ok = false;
                    }
                }
                if ($ok && !is_null($constraint['max_length']) && isset($_POST[$name])) {
                    if (strlen(trim($_POST[$name])) > $constraint['max_length']) {
                        $invalid_parameters[] = "{$name} (too long)";
                    }
                }
            }
        }
        if (count($invalid_parameters) > 0) {
            throw new Exception('Invalid parameter(s): ' . implode(', ', $invalid_parameters));
        }

        // Set the request context/resource link
        if (isset($_POST['resource_link_id'])) {
            $content_item_id = '';
            if (isset($_POST['custom_content_item_id'])) {
                $content_item_id = $_POST['custom_content_item_id'];
            }
            $this->resource_link = new ResourceLink($this->consumer, trim($_POST['resource_link_id']), $content_item_id);
            if (isset($_POST['context_id'])) {
                $this->resource_link->lti_context_id = trim($_POST['context_id']);
            }
            $this->resource_link->lti_resource_id = trim($_POST['resource_link_id']);
            $title = '';
            if (isset($_POST['context_title'])) {
                $title = trim($_POST['context_title']);
            }
            if (isset($_POST['resource_link_title']) && (strlen(trim($_POST['resource_link_title'])) > 0)) {
                if (!empty($title)) {
                    $title .= ': ';
                }
                $title .= trim($_POST['resource_link_title']);
            }
            if (empty($title)) {
                $title = "Course {$this->resource_link->getId()}";
            }
            $this->resource_link->title = $title;

            // Save LTI parameters
            foreach ($this->lti_settings_names as $name) {
                if (isset($_POST[$name])) {
                    $this->resource_link->setSetting($name, $_POST[$name]);
                } else {
                    $this->resource_link->setSetting($name, null);
                }
            }

            // Delete any existing custom parameters
            foreach ($this->resource_link->getSettings() as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resource_link->setSetting($name);
                }
            }

            // Save custom parameters
            foreach ($_POST as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resource_link->setSetting($name, $value);
                }
            }
        }

        // Set the user instance
        $user_id = '';
        if (isset($_POST['user_id'])) {
            $user_id = trim($_POST['user_id']);
        }
        $this->user = new User($this->resource_link, $user_id);

        // Set the user name
        $firstname = (isset($_POST['lis_person_name_given'])) ? $_POST['lis_person_name_given'] : '';
        $lastname = (isset($_POST['lis_person_name_family'])) ? $_POST['lis_person_name_family'] : '';
        $fullname = (isset($_POST['lis_person_name_full'])) ? $_POST['lis_person_name_full'] : '';
        $this->user->setNames($firstname, $lastname, $fullname);

        // Set the user email
        $email = (isset($_POST['lis_person_contact_email_primary'])) ? $_POST['lis_person_contact_email_primary'] : '';
        $this->user->setEmail($email, $this->defaultEmail);

        // Set the user roles
        if (isset($_POST['roles'])) {
            $this->user->roles = ToolProvider::parseRoles($_POST['roles']);
        }

        // Save the user instance
        if (isset($_POST['lis_result_sourcedid'])) {
            if ($this->user->ltiResultSourcedId != $_POST['lis_result_sourcedid']) {
                $this->user->ltiResultSourcedId = $_POST['lis_result_sourcedid'];
                $this->user->save();
            }
        } else if (!empty($this->user->ltiResultSourcedId)) {
            $this->user->delete();
        }

        // Initialise the consumer and check for changes
        $this->consumer->defaultEmail = $this->defaultEmail;
        if ($this->consumer->lti_version != $_POST['lti_version']) {
            $this->consumer->lti_version = $_POST['lti_version'];
            $doSaveConsumer = true;
        }
        if (isset($_POST['tool_consumer_instance_name'])) {
            if ($this->consumer->consumer_name != $_POST['tool_consumer_instance_name']) {
                $this->consumer->consumer_name = $_POST['tool_consumer_instance_name'];
                $doSaveConsumer = true;
            }
        }
        if (isset($_POST['tool_consumer_info_product_family_code'])) {
            $version = $_POST['tool_consumer_info_product_family_code'];
            if (isset($_POST['tool_consumer_info_version'])) {
                $version .= "-{$_POST['tool_consumer_info_version']}";
            }
            // do not delete any existing consumer version if none is passed
            if ($this->consumer->consumer_version != $version) {
                $this->consumer->consumer_version = $version;
                $doSaveConsumer = true;
            }
        } else if (isset($_POST['ext_lms']) && ($this->consumer->consumer_name != $_POST['ext_lms'])) {
            $this->consumer->consumer_version = $_POST['ext_lms'];
            $doSaveConsumer = true;
        }
        if (isset($_POST['tool_consumer_instance_guid'])) {
            if (is_null($this->consumer->consumer_guid)) {
                $this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
                $doSaveConsumer = true;
            } else if (!$this->consumer->protected) {
                $doSaveConsumer = ($this->consumer->consumer_guid != $_POST['tool_consumer_instance_guid']);
                if ($doSaveConsumer) {
                    $this->consumer->consumer_guid = $_POST['tool_consumer_instance_guid'];
                }
            }
        }
        if (isset($_POST['launch_presentation_css_url'])) {
            if ($this->consumer->css_path != $_POST['launch_presentation_css_url']) {
                $this->consumer->css_path = $_POST['launch_presentation_css_url'];
                $doSaveConsumer = true;
            }
        } else if (isset($_POST['ext_launch_presentation_css_url']) &&
            ($this->consumer->css_path != $_POST['ext_launch_presentation_css_url'])
        ) {
            $this->consumer->css_path = $_POST['ext_launch_presentation_css_url'];
            $doSaveConsumer = true;
        } else if (!empty($this->consumer->css_path)) {
            $this->consumer->css_path = null;
            $doSaveConsumer = true;
        }

        // Persist changes to consumer
        if ($doSaveConsumer) {
            $this->consumer->save();
        }

        if (isset($this->resource_link)) {
            // Check if a share arrangement is in place for this resource link
            $this->checkForShare();

            // Persist changes to resource link
            $this->resource_link->save();
        }

        return true;
    }

    /**
     * Check if a share arrangement is in place.
     *
     * @return boolean True if no error is reported
     */
    private function checkForShare()
    {
        $ok = true;
        $doSaveResourceLink = true;

        $key = $this->resource_link->primary_consumer_key;
        $id = $this->resource_link->primary_resource_link_id;

        $shareRequest = isset($_POST['custom_share_key']) && !empty($_POST['custom_share_key']);
        if ($shareRequest) {
            if (!$this->allowSharing) {
                $ok = false;
                $this->reason = 'Your sharing request has been refused because sharing is not being permitted.';
            } else {
                // Check if this is a new share key
                $share_key = new ResourceLinkShareKey($this->resource_link, $_POST['custom_share_key']);
                if (!is_null($share_key->primary_consumer_key) && !is_null($share_key->primary_resource_link_id)) {
                    // Update resource link with sharing primary resource link details
                    $key = $share_key->primary_consumer_key;
                    $id = $share_key->primary_resource_link_id;
                    $ok = ($key != $this->consumer->getKey()) || ($id != $this->resource_link->getId());
                    if ($ok) {
                        $this->resource_link->primary_consumer_key = $key;
                        $this->resource_link->primary_resource_link_id = $id;
                        $this->resource_link->share_approved = $share_key->auto_approve;
                        $ok = $this->resource_link->save();
                        if ($ok) {
                            $doSaveResourceLink = false;
                            $this->user->getResourceLink()->primary_consumer_key = $key;
                            $this->user->getResourceLink()->primary_resource_link_id = $id;
                            $this->user->getResourceLink()->share_approved = $share_key->auto_approve;
                            $this->user->getResourceLink()->updated = time();
                            // Remove share key
                            $share_key->delete();
                        } else {
                            $this->reason = 'An error occurred initialising your share arrangement.';
                        }
                    } else {
                        $this->reason = 'It is not possible to share your resource link with yourself.';
                    }
                }
                if ($ok) {
                    $ok = !is_null($key);
                    if (!$ok) {
                        $this->reason = 'You have requested to share a resource link but none is available.';
                    } else {
                        $ok = (!is_null($this->user->getResourceLink()->share_approved) && $this->user->getResourceLink()->share_approved);
                        if (!$ok) {
                            $this->reason = 'Your share request is waiting to be approved.';
                        }
                    }
                }
            }
        } else {
            // Check no share is in place
            $ok = is_null($key);
            if (!$ok) {
                $this->reason = 'You have not requested to share a resource link but an arrangement is currently in place.';
            }
        }

        // Look up primary resource link
        if ($ok && !is_null($key)) {
            $consumer = new ToolConsumer($key, $this->data_connector);
            $ok = !is_null($consumer->created);
            if ($ok) {
                $resource_link = new ResourceLink($consumer, $id);
                $ok = !is_null($resource_link->created);
            }
            if ($ok) {
                if ($doSaveResourceLink) {
                    $this->resource_link->save();
                }
                $this->resource_link = $resource_link;
            } else {
                $this->reason = 'Unable to load resource link being shared.';
            }
        }

        return $ok;
    }

    /**
     * Validate a parameter value from an array of permitted values.
     *
     * @return void
     * @throws Exception
     */
    private function checkValue($value, $values, $reason = '')
    {
        $ok = in_array($value, $values);
        if (!$ok && !empty($reason)) {
            throw new Exception(sprintf($reason, $value));
        }
    }
}
