<?php

namespace NinjaForms\Includes\Traits;

/**
 * Declare/provide action properties
 */
trait SotGetActionProperties
{

    /**
     * @var string
     */
    protected $_name  = '';
    /**
     * @var string
     */
    protected $_nicename = '';

    /**
     * @var string
     */
    protected $_section = 'installed';

    /**
     * @var string
     */
    protected $_group = '';

    /**
     * @var string
     */
    protected $_image = '';

    /**
     * @var string
     */
    protected $_documentation_url = '';

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = 10;

    /**
     * Get Name
     *
     * Returns the name
     *
     * @return string
     */
    public function get_name(): string
    {
        return $this->_name;
    }

    /**
     * Get Nicename
     *
     * Returns the nicename
     *
     * @return string
     */
    public function get_nicename(): string
    {
        return $this->_nicename;
    }

    /**
     * Get Section
     *
     * Returns the drawer section for an action.
     *
     * @return string
     */
    public function get_section():string
    {
        return $this->_section;
    }

    /**
     * Get Group
     *
     * Returns the drawer group for an action.
     *
     * @return string
     */
    public function get_group():string
    {
        return $this->_group;
    }

    /**
     * Get Image
     *
     * Returns the url of a branded action's image.
     *
     * @return string
     */
    public function get_image():string
    {
        return $this->_image;
    }

    /**
     * Get Documentation URL
     *
     * Returns the action's documentation URL.
     *
     * @return string
     */
    public function get_doc_url():string
    {
        return $this->_documentation_url;
    }

    /**
     * Get Timing
     *
     * Returns the timing for an action.
     *
     * @return mixed
     */
    public function get_timing():int
    {
        $timing = array('early' => -1, 'normal' => 0, 'late' => 1);

        return intval($timing[$this->_timing]);
    }

    /**
     * Get Priority
     *
     * Returns the priority
     *
     * @return int
     */
    public function get_priority(): int
    {
        return intval($this->_priority);
    }
}
