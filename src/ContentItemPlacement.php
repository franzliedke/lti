<?php

namespace Franzl\Lti;

/**
 * Class to represent a content-item placement object
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class ContentItemPlacement
{
    /**
     * Class constructor.
     *
     * @param int $displayWidth Width of item location
     * @param int $displayHeight Height of item location
     * @param string $documentTarget Location to open content in
     * @param string $windowTarget Name of window target
     */
    public function __construct($displayWidth, $displayHeight, $documentTarget, $windowTarget)
    {
        if (!empty($displayWidth)) {
            $this->displayWidth = $displayWidth;
        }
        if (!empty($displayHeight)) {
            $this->displayHeight = $displayHeight;
        }
        if (!empty($documentTarget)) {
            $this->documentTarget = $documentTarget;
        }
        if (!empty($windowTarget)) {
            $this->windowTarget = $windowTarget;
        }
    }
}
