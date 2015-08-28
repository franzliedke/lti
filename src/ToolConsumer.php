<?php

namespace Franzl\Lti;

/**
* Class to represent a tool consumer
*
* @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
* @version 2.5.00
* @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
*/
class ToolConsumer
{

/**
* @var string Local name of tool consumer.
*/
    public $name = null;
/**
* @var string Shared secret.
*/
    public $secret = null;
/**
* @var string LTI version (as reported by last tool consumer connection).
*/
    public $lti_version = null;
/**
* @var string Name of tool consumer (as reported by last tool consumer connection).
*/
    public $consumer_name = null;
/**
* @var string Tool consumer version (as reported by last tool consumer connection).
*/
    public $consumer_version = null;
/**
* @var string Tool consumer GUID (as reported by first tool consumer connection).
*/
    public $consumer_guid = null;
/**
* @var string Optional CSS path (as reported by last tool consumer connection).
*/
    public $css_path = null;
/**
* @var boolean Whether the tool consumer instance is protected by matching the consumer_guid value in incoming requests.
*/
    public $protected = false;
/**
* @var boolean Whether the tool consumer instance is enabled to accept incoming connection requests.
*/
    public $enabled = false;
/**
* @var object Date/time from which the the tool consumer instance is enabled to accept incoming connection requests.
*/
    public $enable_from = null;
/**
* @var object Date/time until which the tool consumer instance is enabled to accept incoming connection requests.
*/
    public $enable_until = null;
/**
* @var object Date of last connection from this tool consumer.
*/
    public $last_access = null;
/**
* @var int Default scope to use when generating an Id value for a user.
*/
    public $id_scope = ToolProvider::ID_SCOPE_ID_ONLY;
/**
* @var string Default email address (or email domain) to use when no email address is provided for a user.
*/
    public $defaultEmail = '';
/**
* @var object Date/time when the object was created.
*/
    public $created = null;
/**
* @var object Date/time when the object was last updated.
*/
    public $updated = null;

/**
* @var string Consumer key value.
*/
    private $key = null;
/**
* @var mixed Data connector object or string.
*/
    private $data_connector = null;

/**
* Class constructor.
*
* @param string  $key             Consumer key
* @param mixed   $data_connector  String containing table name prefix, or database connection object, or array containing one or both values (optional, default is MySQL with an empty table name prefix)
* @param boolean $autoEnable      true if the tool consumers is to be enabled automatically (optional, default is false)
*/
    public function __construct($key = null, $data_connector = '', $autoEnable = false)
    {

        $this->data_connector = DataConnector::getDataConnector($data_connector);
        if (!empty($key)) {
            $this->load($key, $autoEnable);
        } else {
            $this->secret = DataConnector::getRandomString(32);
        }

    }

/**
* Initialise the tool consumer.
*/
    public function initialise()
    {

        $this->key = null;
        $this->name = null;
        $this->secret = null;
        $this->lti_version = null;
        $this->consumer_name = null;
        $this->consumer_version = null;
        $this->consumer_guid = null;
        $this->css_path = null;
        $this->protected = false;
        $this->enabled = false;
        $this->enable_from = null;
        $this->enable_until = null;
        $this->last_access = null;
        $this->id_scope = ToolProvider::ID_SCOPE_ID_ONLY;
        $this->defaultEmail = '';
        $this->created = null;
        $this->updated = null;

    }

/**
* Save the tool consumer to the database.
*
* @return boolean True if the object was successfully saved
*/
    public function save()
    {

        return $this->data_connector->toolConsumerSave($this);

    }

/**
* Delete the tool consumer from the database.
*
* @return boolean True if the object was successfully deleted
*/
    public function delete()
    {

        return $this->data_connector->toolConsumerDelete($this);

    }

/**
* Get the tool consumer key.
*
* @return string Consumer key value
*/
    public function getKey()
    {

        return $this->key;

    }

/**
* Get the data connector.
*
* @return mixed Data connector object or string
*/
    public function getDataConnector()
    {

        return $this->data_connector;

    }

/**
* Is the consumer key available to accept launch requests?
*
* @return boolean True if the consumer key is enabled and within any date constraints
*/
    public function getIsAvailable()
    {

        $ok = $this->enabled;

        $now = time();
        if ($ok && !is_null($this->enable_from)) {
            $ok = $this->enable_from <= $now;
        }
        if ($ok && !is_null($this->enable_until)) {
            $ok = $this->enable_until > $now;
        }

        return $ok;

    }

/**
* Add the OAuth signature to an LTI message.
*
* @param string  $url         URL for message request
* @param string  $type        LTI message type
* @param string  $version     LTI version
* @param array   $params      Message parameters
*
* @return array Array of signed message parameters
*/
    public function signParameters($url, $type, $version, $params)
    {

        if (!empty($url)) {
    // Check for query parameters which need to be included in the signature
            $query_params = [];
            $query_string = parse_url($url, PHP_URL_QUERY);
            if (!is_null($query_string)) {
                $query_items = explode('&', $query_string);
                foreach ($query_items as $item) {
                    if (strpos($item, '=') !== false) {
                        list($name, $value) = explode('=', $item);
                        $query_params[urldecode($name)] = urldecode($value);
                    } else {
                        $query_params[urldecode($item)] = '';
                    }
                }
            }
            $params = $params + $query_params;
    // Add standard parameters
            $params['lti_version'] = $version;
            $params['lti_message_type'] = $type;
            $params['oauth_callback'] = 'about:blank';
    // Add OAuth signature
            $hmac_method = new OAuthSignatureMethodHmacSha1();
            $consumer = new OAuthConsumer($this->getKey(), $this->secret, null);
            $req = OAuthRequest::fromConsumerAndToken($consumer, null, 'POST', $url, $params);
            $req->signRequest($hmac_method, $consumer, null);
            $params = $req->getParameters();
    // Remove parameters being passed on the query string
            foreach (array_keys($query_params) as $name) {
                unset($params[$name]);
            }
        }

        return $params;

    }

###
###  PRIVATE METHOD
###

/**
* Load the tool consumer from the database.
*
* @param string  $key        The consumer key value
* @param boolean $autoEnable True if the consumer should be enabled (optional, default if false)
*
* @return boolean True if the consumer was successfully loaded
*/
    private function load($key, $autoEnable = false)
    {

        $this->initialise();
        $this->key = $key;
        $ok = $this->data_connector->toolConsumerLoad($this);
        if (!$ok) {
            $this->enabled = $autoEnable;
        }

        return $ok;

    }
}
