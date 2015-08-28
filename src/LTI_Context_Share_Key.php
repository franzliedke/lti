<?php

/**
 * Class to represent a tool consumer context share key
 *
 * @deprecated Use LTI_Resource_Link_Share_Key instead
 * @see LTI_Resource_Link_Share_Key
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LTI_Context_Share_Key extends LTI_Resource_Link_Share_Key {

    /**
     * ID for context being shared.
     *
     * @deprecated Use LTI_Resource_Link_Share_Key->primary_resource_link_id instead
     * @see LTI_Resource_Link_Share_Key::$primary_resource_link_id
     */
    public $primary_context_id = NULL;

    /**
     * Class constructor.
     *
     * @param LTI_Resource_Link $resource_link  Resource_Link object
     * @param string      $id      Value of share key (optional, default is null)
     */
    public function __construct($resource_link, $id = NULL) {

        parent::__construct($resource_link, $id);
        $this->primary_context_id = &$this->primary_resource_link_id;

    }

}
