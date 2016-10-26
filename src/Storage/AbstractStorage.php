<?php

namespace Franzl\Lti\Storage;

use Franzl\Lti\ToolConsumer;
use Franzl\Lti\ResourceLinkShareKey;
use Franzl\Lti\User;
use Franzl\Lti\ConsumerNonce;
use Franzl\Lti\ResourceLink;

/**
 * Abstract class to provide a connection to a persistent store for LTI objects
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
abstract class AbstractStorage
{
    /**
     * Default name for database table used to store tool consumers.
     */
    const CONSUMER_TABLE_NAME = 'lti_consumer';
    /**
     * Default name for database table used to store resource links.
     */
    const CONTEXT_TABLE_NAME = 'lti_context';
    const RESOURCE_LINK_TABLE_NAME = 'lti_context';
    /**
     * Default name for database table used to store users.
     */
    const USER_TABLE_NAME = 'lti_user';
    /**
     * Default name for database table used to store resource link share keys.
     */
    const RESOURCE_LINK_SHARE_KEY_TABLE_NAME = 'lti_share_key';
    /**
     * Default name for database table used to store nonce values.
     */
    const NONCE_TABLE_NAME = 'lti_nonce';

    /**
     * @var string SQL date format (default = 'Y-m-d')
     */
    protected $date_format = 'Y-m-d';
    /**
     * @var string SQL time format (default = 'H:i:s')
     */
    protected $time_format = 'H:i:s';

    /**
     * Load tool consumer object.
     *
     * @param mixed $consumer ToolConsumer object
     *
     * @return boolean True if the tool consumer object was successfully loaded
     */
    abstract public function toolConsumerLoad($consumer);

    /**
     * Save tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return boolean True if the tool consumer object was successfully saved
     */
    abstract public function toolConsumerSave($consumer);

    /**
     * Delete tool consumer object.
     *
     * @param ToolConsumer $consumer Consumer object
     *
     * @return boolean True if the tool consumer object was successfully deleted
     */
    abstract public function toolConsumerDelete($consumer);

    /**
     * Load tool consumer objects.
     *
     * @return array Array of all defined ToolConsumer objects
     */
    abstract public function toolConsumerList();

    /**
     * Load resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the resource link object was successfully loaded
     */
    abstract public function resourceLinkLoad($resource_link);

    /**
     * Save resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the resource link object was successfully saved
     */
    abstract public function resourceLinkSave($resource_link);

    /**
     * Delete resource link object.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return boolean True if the Resource_Link object was successfully deleted
     */
    abstract public function resourceLinkDelete($resource_link);

    /**
     * Get array of user objects.
     *
     * @param ResourceLink $resource_link Resource link object
     * @param boolean $local_only True if only users within the resource link are to be returned (excluding users sharing this resource link)
     * @param int $id_scope Scope value to use for user IDs
     *
     * @return array Array of User objects
     */
    abstract public function resourceLinkGetUserResultSourcedIDs($resource_link, $local_only, $id_scope);

    /**
     * Get array of shares defined for this resource link.
     *
     * @param ResourceLink $resource_link Resource_Link object
     *
     * @return array Array of ResourceLinkShare objects
     */
    abstract public function resourceLinkGetShares($resource_link);

    /**
     * Load nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return boolean True if the nonce object was successfully loaded
     */
    abstract public function consumerNonceLoad($nonce);

    /**
     * Save nonce object.
     *
     * @param ConsumerNonce $nonce Nonce object
     *
     * @return boolean True if the nonce object was successfully saved
     */
    abstract public function consumerNonceSave($nonce);

    /**
     * Load resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource_Link share key object
     *
     * @return boolean True if the resource link share key object was successfully loaded
     */
    abstract public function resourceLinkShareKeyLoad($share_key);

    /**
     * Save resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object
     *
     * @return boolean True if the resource link share key object was successfully saved
     */
    abstract public function resourceLinkShareKeySave($share_key);

    /**
     * Delete resource link share key object.
     *
     * @param ResourceLinkShareKey $share_key Resource link share key object
     *
     * @return boolean True if the resource link share key object was successfully deleted
     */
    abstract public function resourceLinkShareKeyDelete($share_key);

    /**
     * Load user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully loaded
     */
    abstract public function userLoad($user);

    /**
     * Save user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully saved
     */
    abstract public function userSave($user);

    /**
     * Delete user object.
     *
     * @param User $user User object
     *
     * @return boolean True if the user object was successfully deleted
     */
    abstract public function userDelete($user);

    /**
     * Create data connector object.
     *
     * A type and table name prefix are required to make a database connection.  The default is to use MySQL with no prefix.
     *
     * If a data connector object is passed, then this is returned unchanged.
     *
     * If the $storage parameter is a string, this is used as the prefix.
     *
     * If the $storage parameter is an array, the first entry should be a prefix string and an optional second entry
     * being a string containing the database type or a database connection object (e.g. the value returned by a call to
     * mysqli_connect() or a PDO object).  A bespoke data connector class can be specified in the optional third parameter.
     *
     * @param mixed $data_connector A data connector object, string or array
     * @param mixed $db A database connection object or string (optional)
     * @param string $type The type of data connector (optional)
     *
     * @return AbstractStorage Data connector object
     */
    public static function getStorage($data_connector, $db = null, $type = null)
    {
        if (!is_null($data_connector)) {
            if (!is_object($data_connector) || !is_subclass_of($data_connector, get_class())) {
                $prefix = null;
                if (is_string($data_connector)) {
                    $prefix = $data_connector;
                } else if (is_array($data_connector)) {
                    for ($i = 0; $i < min(count($data_connector), 3); $i++) {
                        if (is_string($data_connector[$i])) {
                            if (is_null($prefix)) {
                                $prefix = $data_connector[$i];
                            } else if (is_null($type)) {
                                $type = $data_connector[$i];
                            }
                        } else if (is_null($db)) {
                            $db = $data_connector[$i];
                        }
                    }
                } else if (is_object($data_connector)) {
                    $db = $data_connector;
                }
                if (is_null($prefix)) {
                    $prefix = '';
                }
                if (!is_null($db)) {
                    if (is_string($db)) {
                        $type = $db;
                        $db = null;
                    } else if (is_null($type)) {
                        if (is_object($db)) {
                            $type = get_class($db);
                        } else {
                            $type = 'PDO';
                        }
                    }
                }
                if (is_null($type)) {
                    $type = 'PDO';
                }
                $type = strtolower($type);
                $type = __NAMESPACE__ . "{$type}Storage";
                if (is_null($db)) {
                    $data_connector = new $type($prefix);
                } else {
                    $data_connector = new $type($db, $prefix);
                }
            }
        }

        return $data_connector;
    }

    /**
     * Generate a random string.
     *
     * The generated string will only comprise letters (upper- and lower-case) and digits.
     *
     * @param int $length Length of string to be generated (optional, default is 8 characters)
     *
     * @return string Random string
     */
    public static function getRandomString($length = 8)
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        $value = '';
        $charsLength = strlen($chars) - 1;

        for ($i = 1; $i <= $length; $i++) {
            $value .= $chars[rand(0, $charsLength)];
        }

        return $value;
    }

    /**
     * Quote a string for use in a database query.
     *
     * Any single quotes in the value passed will be replaced with two single quotes.  If a null value is passed, a string
     * of 'NULL' is returned (which will never be enclosed in quotes irrespective of the value of the $addQuotes parameter.
     *
     * @param string $value Value to be quoted
     * @param string $addQuotes If true the returned string will be enclosed in single quotes (optional, default is true)
     *
     * @return boolean True if the user object was successfully deleted
     */
    public static function quoted($value, $addQuotes = true)
    {
        if (is_null($value)) {
            $value = 'NULL';
        } else {
            $value = str_replace('\'', '\'\'', $value);
            if ($addQuotes) {
                $value = "'{$value}'";
            }
        }

        return $value;
    }
}
