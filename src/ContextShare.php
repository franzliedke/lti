<?php

namespace Franzl\Lti;

/**
 * Class to represent a tool consumer context share
 *
 * @deprecated Use ResourceLinkShare instead
 * @see LTI_Resource_Link_Share
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContextShare extends ResourceLinkShare {

    /**
     * Context ID value.
     *
     * @deprecated Use ResourceLinkShare->resource_link_id instead
     * @see LTI_Resource_Link_Share::$resource_link_id
     */
    public $context_id = NULL;

    /**
     * Class constructor.
     */
    public function __construct() {

        parent::__construct();
        $this->context_id = &$this->resource_link_id;

    }

}
