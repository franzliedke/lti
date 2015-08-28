<?php

namespace Franzl\Lti;

/**
 * Class to represent a content-item image object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class LTI_Content_Item_Image
{

    /**
     * Class constructor.
     *
     * @param string $id URL of image
     * @param int $height Height of image in pixels (optional)
     * @param int $width Width of image in pixels (optional)
     */
    function __construct($id, $height = NULL, $width = NULL)
    {

        $this->{'@id'} = $id;
        if (!is_null($height)) {
            $this->height = $height;
        }
        if (!is_null($width)) {
            $this->width = $width;
        }

    }

}
