<?php

namespace NinjaForms\Includes\Interfaces;

/**
 * Requirements for NF3 actions on WP 6.7+
 */
interface SotAction
{

    /**
     * Steps to perform when the action/form is saved
     *
     * @param array $actionSettings
     * @return array|void
     */
    public function save(array $actionSettings);

    /**
     * Steps to perform when the action is processed
     *
     * @param array $actionSettings
     * @param int $formId
     * @param array $data
     * @return array
     */
    public function process(array $actionSettings, int $formId, array $data): array;

    /**
     * Return the programmatic name  
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Return the human readable nicename  
     *
     * @return string
     */
    public function get_nicename(): string;

    /**
     * Return the drawer section
     *
     * @return string
     */
    public function get_section(): string;

    /**
     * Return the drawer group
     *
     * @return string
     */
    public function get_group(): string;

    /**
     * Return url of action's image
     *
     * @return string
     */
    public function get_image(): string;

    /**
     * Return url of documentation
     *
     * @return string
     */
    public function get_doc_url(): string;

    /**
     * Return settings
     *
     * Expected array
     * @return array
     */
    public function get_settings();

    /**
     * Return the timing position
     * 
     * Early = -1, Normal = 0, Late = 1
     * 
     * @return integer
     */
    public function get_timing(): int;

    /**
     * Return the priority for the action.
     *
     * @return integer
     */
    public function get_priority(): int;
}
