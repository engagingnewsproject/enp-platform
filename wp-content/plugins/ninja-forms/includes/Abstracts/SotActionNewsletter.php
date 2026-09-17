<?php
namespace NinjaForms\Includes\Abstracts;
use NinjaForms\Includes\Abstracts\SotAction;
use NinjaForms\Includes\Interfaces\SotAction as InterfacesSotAction;

if (! defined('ABSPATH')) exit;

/**
 * Class SotActionNewsletter
 */
abstract class SotActionNewsletter extends SotAction implements InterfacesSotAction
{
    /**
     * @var array
     */
    protected $_tags = array('newsletter');

    protected $_settings = array();

    protected $_transient = '';

    protected $_transient_expiration = '';

    protected $_setting_labels = array(
        'list'   => 'List',
        'fields' => 'List Field Mapping',
        'groups' => 'Interest Groups',
    );

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        if (! $this->_transient) {
            $this->_transient = $this->get_name() . '_newsletter_lists';
        }

        /**
         * Ajax call handled in '_get_lists', but bulk of work could be done in
         * the NewsLetter class that extends this class
         */
        add_action('wp_ajax_nf_' . $this->_name . '_get_lists', array($this, '_get_lists'));

        add_action('init',[$this,'newsletterAbstractInit'],8);
    }

    public function newsletterAbstractInit(): void
    {
        $this->get_list_settings();
    }

    /*
    * PUBLIC METHODS
    */

    public function _get_lists()
    {
        check_ajax_referer('ninja_forms_builder_nonce', 'security');

        $lists = $this->get_lists();

        if ($this->is_failed_lookup($lists)) {

            /*
             * The lookup did not produce a usable list of lists. Report that
             * rather than answering with an empty list, which the builder
             * cannot tell apart from a real answer.
             */
            echo wp_json_encode(array('lists' => array(), 'error' => $this->get_list_lookup_message()));

            wp_die();
        }

        array_unshift($lists, array('value' => 0, 'label' => '-', 'fields' => array(), 'groups' => array()));

        $this->cache_lists($lists);

        echo wp_json_encode(array('lists' => $lists, 'error' => ''));

        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /*
     * PROTECTED METHODS
     */

    abstract protected function get_lists();

    /**
     * Did this lookup fail to produce a usable list of lists?
     *
     * An empty result, or a result holding nothing but the placeholder entry,
     * means the provider could not be reached or refused the request. It is
     * never a real answer, and must not be cached or shown as one.
     *
     * @param mixed $lists
     * @return bool
     */
    protected function is_failed_lookup($lists)
    {
        if (! is_array($lists) || empty($lists)) return true;

        if (1 === count($lists)) {

            $only = reset($lists);

            if (isset($only['value']) && 0 == $only['value']) return true;
        }

        return false;
    }

    /**
     * Explain to the person why their lists could not be loaded.
     *
     * Add-ons override this to name their own service and, where they can,
     * the actual reason.
     *
     * @return string
     */
    protected function get_list_lookup_message()
    {
        return esc_html__('Your lists could not be loaded. The connection to this service may need to be refreshed.', 'ninja-forms');
    }

    /*
     * PRIVATE METHODS
     */

    private function get_list_settings()
    {
        $label_defaults = array(
            'list'   => 'List',
            'fields' => 'List Field Mapping',
            'groups' => 'Interest Groups',
        );
        $labels = array_merge($label_defaults, $this->_setting_labels);

        $prefix = $this->get_name();

        $lists = get_transient($this->_transient);

        /*
         * A cached failure is not an answer. Sites that cached a blank list
         * before this was fixed ask the provider again instead of serving that
         * blank result forever.
         */
        if ($this->is_failed_lookup($lists)) {
            $lists = $this->get_lists();
            $this->cache_lists($lists);
        }

        $lookupFailed = $this->is_failed_lookup($lists);

        if ($lookupFailed) $lists = array();

        $label = $labels['list'] . ' <a class="js-newsletter-list-update extra"><span class="dashicons dashicons-update"></span></a>';

        /*
         * The field is always offered, even when the lists could not be
         * loaded, so the person sees a control and a reason rather than a
         * settings panel with the list silently missing from it.
         */
        if ($lookupFailed) {
            $label .= '<span class="nf-setting-error nf-newsletter-list-error">' . $this->get_list_lookup_message() . '</span>';
        }

        $this->_settings[$prefix . 'newsletter_list'] = array(
            'name' => 'newsletter_list',
            'type' => 'select',
            'label' => $label,
            'width' => 'full',
            'group' => 'primary',
            'value' => '0',
            'options' => array(),
        );

        $fields = array();
        foreach ($lists as $list) {
            $this->_settings[$prefix . 'newsletter_list']['options'][] = $list;

            //Check to see if list has fields array set.
            if (isset($list['fields'])) {

                foreach ($list['fields'] as $field) {
                    $name = $list['value'] . '_' . $field['value'];
                    $fields[] = array(
                        'name' => $name,
                        'type' => 'textbox',
                        'label' => $field['label'],
                        'width' => 'full',
                        'use_merge_tags' => array(
                            'exclude' => array(
                                'user',
                                'post',
                                'system',
                                'querystrings'
                            )
                        )
                    );
                }
            }
        }

        $this->_settings[$prefix . 'newsletter_list_fields'] = array(
            'name' => 'newsletter_list_fields',
            'label' => esc_html__('List Field Mapping', 'ninja-forms'),
            'type' => 'fieldset',
            'group' => 'primary',
            'settings' => array()
        );

        $this->_settings[$prefix . 'newsletter_list_groups'] = array(
            'name' => 'newsletter_list_groups',
            'label' => esc_html__('Interest Groups', 'ninja-forms'),
            'type' => 'fieldset',
            'group' => 'primary',
            'settings' => array()
        );
    }

    private function cache_lists($lists)
    {
        // A failed lookup is never remembered; the next attempt asks again.
        if ($this->is_failed_lookup($lists)) return;

        set_transient($this->_transient, $lists, $this->_transient_expiration);
    }
}
