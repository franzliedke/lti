<?php

namespace Franzl\Lti;

use Franzl\Lti\Storage\AbstractStorage;

/**
 * Class to represent a tool consumer resource link share key
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceLinkShareKey
{
    /**
     * Maximum permitted life for a share key value.
     */
    const MAX_SHARE_KEY_LIFE = 168;  // in hours (1 week)
    /**
     * Default life for a share key value.
     */
    const DEFAULT_SHARE_KEY_LIFE = 24;  // in hours
    /**
     * Minimum length for a share key value.
     */
    const MIN_SHARE_KEY_LENGTH = 5;
    /**
     * Maximum length for a share key value.
     */
    const MAX_SHARE_KEY_LENGTH = 32;

    /**
     * @var string Consumer key for resource link being shared.
     */
    public $primary_consumer_key = null;
    /**
     * @var string ID for resource link being shared.
     */
    public $primary_resource_link_id = null;
    /**
     * @var int Length of share key.
     */
    public $length = null;
    /**
     * @var int Life of share key.
     */
    public $life = null;  // in hours
    /**
     * @var boolean Whether the sharing arrangement should be automatically approved when first used.
     */
    public $auto_approve = false;
    /**
     * @var object Date/time when the share key expires.
     */
    public $expires = null;

    /**
     * @var string Share key value.
     */
    private $id = null;
    /**
     * @var AbstractStorage Data connector.
     */
    private $data_connector = null;

    /**
     * Class constructor.
     *
     * @param ResourceLink $resource_link  Resource_Link object
     * @param string      $id      Value of share key (optional, default is null)
     */
    public function __construct($resource_link, $id = null)
    {
        $this->initialise();
        $this->data_connector = $resource_link->getConsumer()->getStorage();
        $this->id = $id;
        $this->primary_context_id = &$this->primary_resource_link_id;
        if (!empty($id)) {
            $this->load();
        } else {
            $this->primary_consumer_key = $resource_link->getKey();
            $this->primary_resource_link_id = $resource_link->getId();
        }
    }

    /**
     * Initialise the resource link share key.
     */
    public function initialise()
    {
        $this->primary_consumer_key = null;
        $this->primary_resource_link_id = null;
        $this->length = null;
        $this->life = null;
        $this->auto_approve = false;
        $this->expires = null;
    }

    /**
     * Save the resource link share key to the database.
     *
     * @return boolean True if the share key was successfully saved
     */
    public function save()
    {
        if (empty($this->life)) {
            $this->life = self::DEFAULT_SHARE_KEY_LIFE;
        } else {
            $this->life = max(min($this->life, self::MAX_SHARE_KEY_LIFE), 0);
        }
        $this->expires = time() + ($this->life * 60 * 60);
        if (empty($this->id)) {
            if (empty($this->length) || !is_numeric($this->length)) {
                $this->length = self::MAX_SHARE_KEY_LENGTH;
            } else {
                $this->length = max(min($this->length, self::MAX_SHARE_KEY_LENGTH), self::MIN_SHARE_KEY_LENGTH);
            }
            $this->id = AbstractStorage::getRandomString($this->length);
        }

        return $this->data_connector->resourceLinkShareKeySave($this);
    }

    /**
     * Delete the resource link share key from the database.
     *
     * @return boolean True if the share key was successfully deleted
     */
    public function delete()
    {
        return $this->data_connector->resourceLinkShareKeyDelete($this);
    }

    /**
     * Get share key value.
     *
     * @return string Share key value
     */
    public function getId()
    {
        return $this->id;
    }

###
###  PRIVATE METHOD
###

    /**
     * Load the resource link share key from the database.
     */
    private function load()
    {
        $this->initialise();
        $this->data_connector->resourceLinkShareKeyLoad($this);
        if (!is_null($this->id)) {
            $this->length = strlen($this->id);
        }
        if (!is_null($this->expires)) {
            $this->life = ($this->expires - time()) / 60 / 60;
        }
    }
}
