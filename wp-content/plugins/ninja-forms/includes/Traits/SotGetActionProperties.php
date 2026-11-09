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
     * Set Nicename
     *
     * For labels that cannot be translated when the action is constructed.
     * Actions are registered on plugins_loaded, and asking for a translation
     * before init makes WordPress load the textdomain too early and report it
     * as a mistake, once per request. An action in that position stores its
     * label plainly and calls this once init has run.
     *
     * @param string $nicename
     * @return void
     */
    public function set_nicename( string $nicename ): void
    {
        $this->_nicename = $nicename;
    }

    /**
     * Set Documentation URL
     *
     * For actions re-instantiated under another name, such as a Collect
     * Payment locked to one gateway, whose documentation is not the generic
     * action's. Shown as the help link beside the action's name.
     *
     * @param string $url
     * @return void
     */
    public function set_documentation_url( string $url ): void
    {
        $this->_documentation_url = $url;
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
