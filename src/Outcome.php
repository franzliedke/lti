<?php

namespace Franzl\Lti;

/**
 * Class to represent an outcome
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class Outcome
{
    /**
     * Language value.
     *
     * @var string
     */
    public $language = null;

    /**
     * Outcome status value.
     *
     * @var string
     */
    public $status = null;

    /**
     * Outcome date value.
     *
     * @var object
     */
    public $date = null;

    /**
     * Outcome type value.
     *
     * @var string
     */
    public $type = null;

    /**
     * Outcome data source value.
     *
     * @var string
     */
    public $data_source = null;

    /**
     * Result sourcedid.
     *
     * @var string
     * @deprecated Use User object instead
     */
    private $sourcedid = null;

    /**
     * Outcome value.
     *
     * @var string
     */
    private $value = null;

    /**
     * Class constructor.
     *
     * @param string $sourcedid Result sourcedid value for the user/resource link (optional, default is to use associated User object)
     * @param string $value     Outcome value (optional, default is none)
     */
    public function __construct($sourcedid = null, $value = null)
    {
        $this->sourcedid = $sourcedid;
        $this->value = $value;
        $this->language = 'en-US';
        $this->date = gmdate('Y-m-d\TH:i:s\Z', time());
        $this->type = 'decimal';
    }

    /**
     * Get the result sourcedid value.
     *
     * @deprecated Use User object instead
     *
     * @return string Result sourcedid value
     */
    public function getSourcedid()
    {
        return $this->sourcedid;
    }

    /**
     * Get the outcome value.
     *
     * @return string Outcome value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the outcome value.
     *
     * @param string Outcome value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
