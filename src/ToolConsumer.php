<?php

namespace Franzl\Lti;

use Franzl\Lti\OAuth\Consumer;
use Franzl\Lti\OAuth\Request;
use Franzl\Lti\OAuth\Signature\HmacSha1;
use Franzl\Lti\Storage\AbstractStorage;

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
     * Local name of tool consumer.
     *
     * @var string
     */
    public $name = null;

    /**
     * Shared secret.
     *
     * @var string
     */
    public $secret = null;

    /**
     * LTI version (as reported by last tool consumer connection).
     *
     * @var string
     */
    public $ltiVersion = null;

    /**
     * Name of tool consumer (as reported by last tool consumer connection).
     *
     * @var string
     */
    public $consumerName = null;

    /**
     * Tool consumer version (as reported by last tool consumer connection).
     *
     * @var string
     */
    public $consumerVersion = null;

    /**
     * Tool consumer GUID (as reported by first tool consumer connection).
     *
     * @var string
     */
    public $consumerGuid = null;

    /**
     * Optional CSS path (as reported by last tool consumer connection).
     *
     * @var string
     */
    public $cssPath = null;

    /**
     * Whether the tool consumer instance is protected by matching the consumer_guid value in incoming requests.
     *
     * @var boolean
     */
    public $protected = false;

    /**
     * Whether the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var boolean
     */
    public $enabled = false;

    /**
     * Date/time from which the the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var object
     */
    public $enableFrom = null;

    /**
     * Date/time until which the tool consumer instance is enabled to accept incoming connection requests.
     *
     * @var object
     */
    public $enableUntil = null;

    /**
     * Date of last connection from this tool consumer.
     *
     * @var object
     */
    public $lastAccess = null;

    /**
     * Default scope to use when generating an Id value for a user.
     *
     * @var int
     */
    public $idScope = ToolProvider::ID_SCOPE_ID_ONLY;

    /**
     * Default email address (or email domain) to use when no email address is provided for a user.
     *
     * @var string
     */
    public $defaultEmail = '';

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
     * Consumer key value.
     *
     * @var string
     */
    private $key = null;

    /**
     * Data connector object or string.
     *
     * @var mixed
     */
    private $storage = null;

    /**
     * Class constructor.
     *
     * @param string  $key             Consumer key
     * @param mixed   $storage         String containing table name prefix, or database connection object, or array containing one or both values (optional, default is MySQL with an empty table name prefix)
     * @param boolean $autoEnable      true if the tool consumers is to be enabled automatically (optional, default is false)
     */
    public function __construct($key = null, $storage = '', $autoEnable = false)
    {
        $this->storage = AbstractStorage::getStorage($storage);
        if (!empty($key)) {
            $this->load($key, $autoEnable);
        } else {
            $this->secret = AbstractStorage::getRandomString(32);
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
        $this->ltiVersion = null;
        $this->consumerName = null;
        $this->consumerVersion = null;
        $this->consumerGuid = null;
        $this->cssPath = null;
        $this->protected = false;
        $this->enabled = false;
        $this->enableFrom = null;
        $this->enableUntil = null;
        $this->lastAccess = null;
        $this->idScope = ToolProvider::ID_SCOPE_ID_ONLY;
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
        return $this->storage->toolConsumerSave($this);
    }

    /**
     * Delete the tool consumer from the database.
     *
     * @return boolean True if the object was successfully deleted
     */
    public function delete()
    {
        return $this->storage->toolConsumerDelete($this);
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
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Is the consumer key available to accept launch requests?
     *
     * @return boolean True if the consumer key is enabled and within any date constraints
     */
    public function isAvailable()
    {
        $ok = $this->enabled;

        $now = time();
        if ($ok && !is_null($this->enableFrom)) {
            $ok = $this->enableFrom <= $now;
        }
        if ($ok && !is_null($this->enableUntil)) {
            $ok = $this->enableUntil > $now;
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
            $hmac_method = new HmacSha1();
            $consumer = new Consumer($this->getKey(), $this->secret, null);
            $req = Request::fromConsumerAndToken($consumer, null, 'POST', $url, $params);
            $req->signRequest($hmac_method, $consumer, null);
            $params = $req->getParameters();
            // Remove parameters being passed on the query string
            foreach (array_keys($query_params) as $name) {
                unset($params[$name]);
            }
        }

        return $params;
    }

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
        $ok = $this->storage->toolConsumerLoad($this);
        if (!$ok) {
            $this->enabled = $autoEnable;
        }

        return $ok;
    }
}
