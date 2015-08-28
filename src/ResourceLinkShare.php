<?php

namespace Franzl\Lti;

/**
 * Class to represent a tool consumer resource link share
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ResourceLinkShare
{

    /**
     * @var string Consumer key value.
     */
    public $consumer_key = null;
    /**
     * @var string Resource link ID value.
     */
    public $resource_link_id = null;
    /**
     * @var string Title of sharing context.
     */
    public $title = null;
    /**
     * @var boolean Whether sharing request is to be automatically approved on first use.
     */
    public $approved = null;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->context_id = &$this->resource_link_id;
    }
}
