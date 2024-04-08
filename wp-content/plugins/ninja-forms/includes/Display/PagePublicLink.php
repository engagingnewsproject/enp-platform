<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Display_PagePublicLink extends NF_Display_Page
{
	protected $form_id;
	protected $form;

	public function __construct($form_id)
	{
		$this->form_id = $form_id;
		$this->form = Ninja_Forms()->form($this->form_id)->get();

		if($this->form->get_setting('allow_public_link')) {
			parent::__construct();
		}
	}

	/**
	 * @return string HTML
	 */
	public function get_content()
	{
		return "[ninja_forms id='$this->form_id']";
	}

	/**
	 * @return string
	 */
    public function get_title()
    {
		/** 
         * Public form pages should not have visible page titles.
         * Specifically write out an empty span to keep themes from being cute
         *   and saying "Untitled" on pages without a title.
         */
        return '<span style="display:none;"></span>';
    }

	/**
	 * @return string
	 */
    public function get_guid()
    {
        return 'ninja-forms-public-form';
    }
}
