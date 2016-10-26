<?php

namespace Franzl\Lti;

use Exception;
use Franzl\Lti\OAuth\DataStore;
use Franzl\Lti\OAuth\Request;
use Franzl\Lti\OAuth\Server;
use Franzl\Lti\OAuth\Signature\HmacSha1;
use Franzl\Lti\Storage\AbstractStorage;
use Psr\Http\Message\ServerRequestInterface;

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
     *
     * @var string
     */
    const CONNECTION_ERROR_MESSAGE = 'Sorry, there was an error connecting you to the application.';

    /**
     * LTI version 1 for messages.
     *
     * @var string
     */
    const LTI_VERSION1 = 'LTI-1p0';

    /**
     * LTI version 2 for messages.
     *
     * @var string
     */
    const LTI_VERSION2 = 'LTI-2p0';

    /**
     * Use ID value only.
     *
     * @var int
     */
    const ID_SCOPE_ID_ONLY = 0;

    /**
     * Prefix an ID with the consumer key.
     *
     * @var int
     */
    const ID_SCOPE_GLOBAL = 1;

    /**
     * Prefix the ID with the consumer key and context ID.
     *
     * @var int
     */
    const ID_SCOPE_CONTEXT = 2;

    /**
     * Prefix the ID with the consumer key and resource ID.
     *
     * @var int
     */
    const ID_SCOPE_RESOURCE = 3;

    /**
     * Character used to separate each element of an ID.
     *
     * @var string
     */
    const ID_SCOPE_SEPARATOR = ':';

    /**
     * Tool Consumer object.
     *
     * @var ToolConsumer
     */
    public $consumer = null;

    /**
     * Return URL provided by tool consumer.
     *
     * @var string
     */
    public $returnUrl = null;

    /**
     * User object.
     *
     * @var User
     */
    public $user = null;

    /**
     * Resource link object.
     *
     * @var ResourceLink
     */
    public $resourceLink = null;

    /**
     * Storage object.
     *
     * @var AbstractStorage
     */
    public $storage = null;

    /**
     * Default email domain.
     *
     * @var string
     */
    public $defaultEmail = '';

    /**
     * Scope to use for user IDs.
     *
     * @var int
     */
    public $idScope = self::ID_SCOPE_ID_ONLY;

    /**
     * Whether shared resource link arrangements are permitted.
     *
     * @var bool
     */
    public $allowSharing = false;

    /**
     * Message for last request processed.
     *
     * @var string
     */
    public $message = self::CONNECTION_ERROR_MESSAGE;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string
     */
    protected $redirectUrl = null;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string
     */
    protected $mediaTypes = null;

    /**
     * URL to redirect user to on successful completion of the request.
     *
     * @var string
     */
    protected $documentTargets = null;

    /**
     * HTML to be displayed on a successful completion of the request.
     *
     * @var string
     */
    protected $output = null;

    /**
     * HTML to be displayed on an unsuccessful completion of the request and no return URL is available.
     *
     * @var string
     */
    protected $errorOutput = null;

    /**
     * Whether debug messages explaining the cause of errors are to be returned to the tool consumer.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Callback functions for handling requests.
     *
     * @var array
     */
    private $callbackHandler = null;

    /**
     * LTI parameter constraints for auto validation checks.
     *
     * @var array
     */
    private $constraints = null;

    /**
     * List of supported message types and associated callback type names.
     *
     * @var array
     */
    private $messageTypes = [
        'basic-lti-launch-request'    => 'launch',
        'ConfigureLaunchRequest'      => 'configure',
        'DashboardRequest'            => 'dashboard',
        'ContentItemSelectionRequest' => 'content-item'
    ];

    /**
     * List of supported message types and associated class methods
     *
     * @var array
     */
    private $methodNames = [
        'basic-lti-launch-request'    => 'onLaunch',
        'ConfigureLaunchRequest'      => 'onConfigure',
        'DashboardRequest'            => 'onDashboard',
        'ContentItemSelectionRequest' => 'onContentItem'
    ];

    /**
     * Names of LTI parameters to be retained in the settings property.
     *
     * @var array
     */
    private $ltiSettingsNames = [
        'ext_resource_link_content',
        'ext_resource_link_content_signature',
        'lis_result_sourcedid',
        'lis_outcome_service_url',
        'ext_ims_lis_basic_outcome_url',
        'ext_ims_lis_resultvalue_sourcedids',
        'ext_ims_lis_memberships_id',
        'ext_ims_lis_memberships_url',
        'ext_ims_lti_tool_setting',
        'ext_ims_lti_tool_setting_id',
        'ext_ims_lti_tool_setting_url'
    ];

    /**
     * Permitted LTI versions for messages.
     *
     * @var array
     */
    private $LTI_VERSIONS = [self::LTI_VERSION1, self::LTI_VERSION2];

    /**
     * Class constructor
     *
     * @param AbstractStorage $storage Object containing a database connection object (optional, default is a blank prefix and MySQL)
     * @param mixed $callbackHandler String containing name of callback function for launch request, or associative array of callback functions for each request type
     */
    public function __construct(AbstractStorage $storage = null, $callbackHandler = null)
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
        $this->storage = AbstractStorage::getStorage($storage);
    }

    /**
     * Process an incoming request
     *
     * @param ServerRequestInterface $request
     * @return mixed Returns TRUE or FALSE, a redirection URL or HTML
     * @throws Exception
     */
    public function handleRequest(ServerRequestInterface $request)
    {
        $requestBody = (array) $request->getParsedBody();

        // Set debug mode
        $this->debugMode = isset($requestBody['custom_debug']) && (strtolower($requestBody['custom_debug']) == 'true');

        // Set return URL if available
        if (isset($requestBody['launch_presentation_return_url'])) {
            $this->returnUrl = $requestBody['launch_presentation_return_url'];
        } else if (isset($requestBody['content_item_return_url'])) {
            $this->returnUrl = $requestBody['content_item_return_url'];
        }

        // Perform action
        if ($this->authenticate($request)) {
            $this->doCallback($requestBody);
        }

        $this->result();
    }

    /**
     * Add a parameter constraint to be checked on launch
     *
     * @param string $name Name of parameter to be checked
     * @param bool $required True if parameter is required (optional, default is TRUE)
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
        $this->storage = AbstractStorage::getStorage($this->storage);

        return $this->storage->toolConsumerList();
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
     * @return bool True if no error
     */
    protected function onLaunch()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid configure request
     *
     * @return bool True if no error
     */
    protected function onConfigure()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid dashboard request
     *
     * @return bool True if no error
     */
    protected function onDashboard()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a valid content-item request
     *
     * @return bool True if no error
     */
    protected function onContentItem()
    {
        $this->doCallbackMethod();
    }

    /**
     * Process a response to an invalid request
     *
     * @return bool True if no further error processing required
     */
    protected function onError()
    {
        $this->doCallbackMethod('error');
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirectUrl and output properties.
     *
     * @param array $body The request body
     * @return bool True if no error reported
     */
    private function doCallback(array $body)
    {
        $method = $this->methodNames[$body['lti_message_type']];
        $this->$method();
    }

    /**
     * Call any callback function for the requested action.
     *
     * This function may set the redirectUrl and output properties.
     *
     * @param string $type Callback type
     *
     * @return bool True if no error reported
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

            // Callback function may return HTML or a redirect URL
            if (is_string($result)) {
                if ((substr($result, 0, 7) == 'http://') || (substr($result, 0, 8) == 'https://')) {
                    $this->redirectUrl = $result;
                } else {
                    if (is_null($this->output)) {
                        $this->output = '';
                    }
                    $this->output .= $result;
                }
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
        // TODO: Call $this->onError(); hook

        // FIXME: Should be executed when exception is caught
        if (false) {
            // If not valid, return an error message to the tool consumer if a return URL is provided
            if (!empty($this->returnUrl)) {
                $error_url = $this->returnUrl;
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
                if (!is_null($this->errorOutput)) {
                    echo $this->errorOutput;
                } else if ($this->debugMode && !empty($this->reason)) {
                    echo "Debug error: {$this->reason}";
                } else {
                    echo "Error: {$this->message}";
                }
            }
        } else if (!is_null($this->redirectUrl)) {
            header("Location: {$this->redirectUrl}");
            exit;
        } else if (!is_null($this->output)) {
            echo $this->output;
        }
    }

    /**
     * Check the authenticity of the LTI launch request.
     *
     * The consumer, resource link and user objects will be initialised if the request is valid.
     *
     * @param ServerRequestInterface $request
     * @return bool True if the request has been successfully validated.
     * @throws Exception
     * @internal param array $requestBody
     */
    private function authenticate(ServerRequestInterface $request)
    {
        $requestBody = (array) $request->getParsedBody();

        // Get the consumer
        $doSaveConsumer = false;

        // Check all required launch parameters
        $version = isset($requestBody['lti_version']) ? $requestBody['lti_version'] : '';
        $messageType = isset($requestBody['lti_message_type']) ? $requestBody['lti_message_type'] : '';

        if (!in_array($version, $this->LTI_VERSIONS)) {
            throw new Exception('Invalid or missing lti_version parameter');
        }

        switch ($messageType) {
            case 'basic-lti-launch-request':
            case 'DashboardRequest':
                if (!isset($requestBody['resource_link_id']) || (strlen(trim($requestBody['resource_link_id'])) == 0)) {
                    throw new Exception('Missing resource link ID');
                }
                break;
            case 'ContentItemSelectionRequest':
                $acceptMediaTypes = isset($requestBody['accept_media_types']) ? trim($requestBody['accept_media_types']) : '';
                if (strlen($acceptMediaTypes) == 0) {
                    throw new Exception('No accept_media_types found');
                }

                $mediaTypes = array_filter(explode(',', str_replace(' ', '', $acceptMediaTypes)), 'strlen');
                $mediaTypes = array_unique($mediaTypes);

                if (count($mediaTypes) == 0) {
                    throw new Exception('No valid accept_media_types found');
                }

                $this->mediaTypes = $mediaTypes;

                $acceptDocumentTargets = isset($requestBody['accept_presentation_document_targets']) ? trim($requestBody['accept_presentation_document_targets']) : '';
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

                $returnUrl = isset($requestBody['content_item_return_url']) ? trim($requestBody['content_item_return_url']) : '';

                if (strlen($returnUrl) == 0) {
                    throw new Exception('Missing content_item_return_url parameter');
                }

                break;
            default:
                throw new Exception('Invalid or missing lti_message_type parameter');
        }

        // Check consumer key
        if (!isset($requestBody['oauth_consumer_key'])) {
            throw new Exception('Missing consumer key');
        }

        $this->consumer = new ToolConsumer($requestBody['oauth_consumer_key'], $this->storage);
        if (is_null($this->consumer->created)) {
            throw new Exception('Invalid consumer key');
        }

        $now = time();
        $today = date('Y-m-d', $now);
        if (is_null($this->consumer->lastAccess)) {
            $doSaveConsumer = true;
        } else {
            $last = date('Y-m-d', $this->consumer->lastAccess);
            $doSaveConsumer = $doSaveConsumer || ($last != $today);
        }
        $this->consumer->lastAccess = $now;

        $store = new DataStore($this);
        $server = new Server($store);
        $method = new HmacSha1();
        $server->addSignatureMethod($method);
        $request = Request::fromPsrRequest($request);
        $server->verifyRequest($request);

        if ($this->consumer->protected) {
            $consumerGuid = isset($requestBody['tool_consumer_instance_guid']) ? $requestBody['tool_consumer_instance_guid'] : '';

            if (empty($consumerGuid)) {
                throw new Exception('A tool consumer GUID must be included in the launch request');
            }

            if ($this->consumer->consumerGuid !== $consumerGuid) {
                throw new Exception('Request is from an invalid tool consumer');
            }
        }

        if (!$this->consumer->enabled) {
            throw new Exception('Tool consumer has not been enabled by the tool provider');
        }

        if (!is_null($this->consumer->enableFrom) && ($this->consumer->enableFrom > $now)) {
            throw new Exception('Tool consumer access is not yet available');
        }

        if (!is_null($this->consumer->enableUntil) && ($this->consumer->enableUntil <= $now)) {
            throw new Exception('Tool consumer access has expired');
        }

        // Validate other message parameter values
        if ($requestBody['lti_message_type'] != 'ContentItemSelectionRequest') {
            if (isset($requestBody['launch_presentation_document_target'])) {
                $this->checkValue(
                    $requestBody['launch_presentation_document_target'],
                    ['embed', 'frame', 'iframe', 'window', 'popup', 'overlay'],
                    'Invalid value for launch_presentation_document_target parameter: %s.'
                );
            }
        } else {
            if (isset($requestBody['accept_unsigned'])) {
                $this->checkValue($requestBody['accept_unsigned'], ['true', 'false'], 'Invalid value for accept_unsigned parameter: %s.');
            }
            if (isset($requestBody['accept_multiple'])) {
                $this->checkValue($requestBody['accept_multiple'], ['true', 'false'], 'Invalid value for accept_multiple parameter: %s.');
            }
            if (isset($requestBody['accept_copy_advice'])) {
                $this->checkValue($requestBody['accept_copy_advice'], ['true', 'false'], 'Invalid value for accept_copy_advice parameter: %s.');
            }
            if (isset($requestBody['auto_create'])) {
                $this->checkValue($requestBody['auto_create'], ['true', 'false'], 'Invalid value for auto_create parameter: %s.');
            }
            if (isset($requestBody['can_confirm'])) {
                $this->checkValue($requestBody['can_confirm'], ['true', 'false'], 'Invalid value for can_confirm parameter: %s.');
            }
        }

        // Validate message parameter constraints
        $invalid_parameters = [];
        foreach ($this->constraints as $name => $constraint) {
            if (empty($constraint['messages']) || in_array($messageType, $constraint['messages'])) {
                $ok = true;
                if ($constraint['required']) {
                    if (!isset($requestBody[$name]) || (strlen(trim($requestBody[$name])) <= 0)) {
                        $invalid_parameters[] = "{$name} (missing)";
                        $ok = false;
                    }
                }
                if ($ok && !is_null($constraint['max_length']) && isset($requestBody[$name])) {
                    if (strlen(trim($requestBody[$name])) > $constraint['max_length']) {
                        $invalid_parameters[] = "{$name} (too long)";
                    }
                }
            }
        }
        if (count($invalid_parameters) > 0) {
            throw new Exception('Invalid parameter(s): ' . implode(', ', $invalid_parameters));
        }

        // Set the request context/resource link
        if (isset($requestBody['resource_link_id'])) {
            $content_item_id = '';
            if (isset($requestBody['custom_content_item_id'])) {
                $content_item_id = $requestBody['custom_content_item_id'];
            }
            $this->resourceLink = new ResourceLink($this->consumer, trim($requestBody['resource_link_id']), $content_item_id);
            if (isset($requestBody['context_id'])) {
                $this->resourceLink->lti_context_id = trim($requestBody['context_id']);
            }
            $this->resourceLink->lti_resource_id = trim($requestBody['resource_link_id']);
            $title = '';
            if (isset($requestBody['context_title'])) {
                $title = trim($requestBody['context_title']);
            }
            if (isset($requestBody['resource_link_title']) && (strlen(trim($requestBody['resource_link_title'])) > 0)) {
                if (!empty($title)) {
                    $title .= ': ';
                }
                $title .= trim($requestBody['resource_link_title']);
            }
            if (empty($title)) {
                $title = "Course {$this->resourceLink->getId()}";
            }
            $this->resourceLink->title = $title;

            // Save LTI parameters
            foreach ($this->ltiSettingsNames as $name) {
                if (isset($requestBody[$name])) {
                    $this->resourceLink->setSetting($name, $requestBody[$name]);
                } else {
                    $this->resourceLink->setSetting($name, null);
                }
            }

            // Delete any existing custom parameters
            foreach ($this->resourceLink->getSettings() as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resourceLink->setSetting($name);
                }
            }

            // Save custom parameters
            foreach ($requestBody as $name => $value) {
                if (strpos($name, 'custom_') === 0) {
                    $this->resourceLink->setSetting($name, $value);
                }
            }
        }

        // Set the user instance
        $user_id = '';
        if (isset($requestBody['user_id'])) {
            $user_id = trim($requestBody['user_id']);
        }
        $this->user = new User($this->resourceLink, $user_id);

        // Set the user name
        $firstname = (isset($requestBody['lis_person_name_given'])) ? $requestBody['lis_person_name_given'] : '';
        $lastname = (isset($requestBody['lis_person_name_family'])) ? $requestBody['lis_person_name_family'] : '';
        $fullname = (isset($requestBody['lis_person_name_full'])) ? $requestBody['lis_person_name_full'] : '';
        $this->user->setNames($firstname, $lastname, $fullname);

        // Set the user email
        $email = (isset($requestBody['lis_person_contact_email_primary'])) ? $requestBody['lis_person_contact_email_primary'] : '';
        $this->user->setEmail($email, $this->defaultEmail);

        // Set the user roles
        if (isset($requestBody['roles'])) {
            $this->user->roles = ToolProvider::parseRoles($requestBody['roles']);
        }

        // Save the user instance
        if (isset($requestBody['lis_result_sourcedid'])) {
            if ($this->user->ltiResultSourcedId != $requestBody['lis_result_sourcedid']) {
                $this->user->ltiResultSourcedId = $requestBody['lis_result_sourcedid'];
                $this->user->save();
            }
        } else if (!empty($this->user->ltiResultSourcedId)) {
            $this->user->delete();
        }

        // Initialise the consumer and check for changes
        $this->consumer->defaultEmail = $this->defaultEmail;
        if ($this->consumer->ltiVersion != $requestBody['lti_version']) {
            $this->consumer->ltiVersion = $requestBody['lti_version'];
            $doSaveConsumer = true;
        }
        if (isset($requestBody['tool_consumer_instance_name'])) {
            if ($this->consumer->consumerName != $requestBody['tool_consumer_instance_name']) {
                $this->consumer->consumerName = $requestBody['tool_consumer_instance_name'];
                $doSaveConsumer = true;
            }
        }
        if (isset($requestBody['tool_consumer_info_product_family_code'])) {
            $version = $requestBody['tool_consumer_info_product_family_code'];
            if (isset($requestBody['tool_consumer_info_version'])) {
                $version .= "-{$requestBody['tool_consumer_info_version']}";
            }
            // do not delete any existing consumer version if none is passed
            if ($this->consumer->consumerVersion != $version) {
                $this->consumer->consumerVersion = $version;
                $doSaveConsumer = true;
            }
        } else if (isset($requestBody['ext_lms']) && ($this->consumer->consumerName != $requestBody['ext_lms'])) {
            $this->consumer->consumerVersion = $requestBody['ext_lms'];
            $doSaveConsumer = true;
        }
        if (isset($requestBody['tool_consumer_instance_guid'])) {
            if (is_null($this->consumer->consumerGuid)) {
                $this->consumer->consumerGuid = $requestBody['tool_consumer_instance_guid'];
                $doSaveConsumer = true;
            } else if (!$this->consumer->protected) {
                $doSaveConsumer = ($this->consumer->consumerGuid != $requestBody['tool_consumer_instance_guid']);
                if ($doSaveConsumer) {
                    $this->consumer->consumerGuid = $requestBody['tool_consumer_instance_guid'];
                }
            }
        }
        if (isset($requestBody['launch_presentation_css_url'])) {
            if ($this->consumer->cssPath != $requestBody['launch_presentation_css_url']) {
                $this->consumer->cssPath = $requestBody['launch_presentation_css_url'];
                $doSaveConsumer = true;
            }
        } else if (isset($requestBody['ext_launch_presentation_css_url']) &&
            ($this->consumer->cssPath != $requestBody['ext_launch_presentation_css_url'])
        ) {
            $this->consumer->cssPath = $requestBody['ext_launch_presentation_css_url'];
            $doSaveConsumer = true;
        } else if (!empty($this->consumer->cssPath)) {
            $this->consumer->cssPath = null;
            $doSaveConsumer = true;
        }

        // Persist changes to consumer
        if ($doSaveConsumer) {
            $this->consumer->save();
        }

        if (isset($this->resourceLink)) {
            // Check if a share arrangement is in place for this resource link
            $this->checkForShare($requestBody);

            // Persist changes to resource link
            $this->resourceLink->save();
        }

        return true;
    }

    /**
     * Check if a share arrangement is in place.
     *
     * @param array $requestBody
     * @throws Exception
     */
    private function checkForShare(array $requestBody)
    {
        $doSaveResourceLink = true;

        $key = $this->resourceLink->primary_consumer_key;
        $id = $this->resourceLink->primary_resource_link_id;

        $shareRequest = isset($requestBody['custom_share_key']) && !empty($requestBody['custom_share_key']);
        if ($shareRequest) {
            if (!$this->allowSharing) {
                throw new Exception('Your sharing request has been refused because sharing is not being permitted');
            } else {
                // Check if this is a new share key
                $share_key = new ResourceLinkShareKey($this->resourceLink, $requestBody['custom_share_key']);
                if (!is_null($share_key->primary_consumer_key) && !is_null($share_key->primary_resource_link_id)) {
                    // Update resource link with sharing primary resource link details
                    $key = $share_key->primary_consumer_key;
                    $id = $share_key->primary_resource_link_id;

                    if ($key == $this->consumer->getKey() && $id == $this->resourceLink->getId()) {
                        throw new Exception('It is not possible to share your resource link with yourself');
                    }

                    $this->resourceLink->primary_consumer_key = $key;
                    $this->resourceLink->primary_resource_link_id = $id;
                    $this->resourceLink->share_approved = $share_key->auto_approve;

                    if (!$this->resourceLink->save()) {
                        throw new Exception('An error occurred initialising your share arrangement');
                    }

                    $doSaveResourceLink = false;
                    $this->user->getResourceLink()->primary_consumer_key = $key;
                    $this->user->getResourceLink()->primary_resource_link_id = $id;
                    $this->user->getResourceLink()->share_approved = $share_key->auto_approve;
                    $this->user->getResourceLink()->updated = time();
                    // Remove share key
                    $share_key->delete();
                }

                if (!is_null($key)) {
                    throw new Exception('You have requested to share a resource link but none is available');
                }

                if (is_null($this->user->getResourceLink()->share_approved) || !$this->user->getResourceLink()->share_approved) {
                    throw new Exception('Your share request is waiting to be approved');
                }
            }
        } else if (!is_null($key)) {
            // Check no share is in place
            throw new Exception('You have not requested to share a resource link but an arrangement is currently in place');
        }

        // Look up primary resource link
        if (!is_null($key)) {
            $consumer = new ToolConsumer($key, $this->storage);

            // TODO: Move to load function
            if (is_null($consumer->created)) {
                throw new Exception('Unable to load tool consumer');
            }

            $resource_link = new ResourceLink($consumer, $id);

            if (is_null($resource_link->created)) {
                throw new Exception('Unable to load resource link being shared');
            }

            if ($doSaveResourceLink) {
                $this->resourceLink->save();
            }
            $this->resourceLink = $resource_link;
        }
    }

    /**
     * Validate a parameter value from an array of permitted values.
     *
     * @param string $value
     * @param array $values
     * @param string $reason
     * @throws Exception
     */
    private function checkValue($value, array $values, $reason = '')
    {
        $ok = in_array($value, $values);
        if (!$ok && !empty($reason)) {
            throw new Exception(sprintf($reason, $value));
        }
    }
}
