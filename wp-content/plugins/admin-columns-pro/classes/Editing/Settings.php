<?php

namespace ACP\Editing;

use AC;
use AC\View;

class Settings extends AC\Settings\Column
    implements AC\Settings\Header
{

    const NAME = 'edit';

    private $edit;

    /**
     * @var AC\Settings\Column[]
     */
    protected $sections = [];

    /**
     * @var boolean
     */
    private $refresh_column;

    public function __construct(AC\Column $column, $refresh_column = false)
    {
        parent::__construct($column);

        $this->set_refresh($refresh_column);
    }

    /**
     * @param boolean $enable
     */
    public function set_refresh($enable)
    {
        $this->refresh_column = $enable;
    }

    /**
     * @param AC\Settings\Column $setting
     *
     * @return $this
     */
    public function add_section(AC\Settings\Column $setting)
    {
        $this->sections[$setting->get_name()] = $setting;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return AC\Settings\Column|null
     */
    public function get_section($name)
    {
        return $this->sections[$name] ?? null;
    }

    protected function define_options()
    {
        return [self::NAME => 'on'];
    }

    /**
     * @return string
     */
    private function get_instruction()
    {
        $view = new View([
            'object_type' => $this->column->get_list_singular_label(),
        ]);
        $view->set_template('tooltip/inline-editing');

        return $view->render();
    }

    public function create_header_view()
    {
        $filter = $this->get_edit();

        $view = new View([
            'title'    => __('Enable Editing', 'codepress-admin-columns'),
            'dashicon' => 'dashicons-edit',
            'state'    => $filter,
        ]);

        $view->set_template('settings/header-icon');

        return $view;
    }

    protected function create_toggle_element()
    {
        $setting = new AC\Form\Element\Toggle(self::NAME, '', $this->get_value(self::NAME) === 'on', 'on', 'off');
        $setting->add_class('ac-setting-input_' . self::NAME);

        if ($this->refresh_column) {
            $setting->set_attribute('data-refresh', 'column');
        }

        return $setting;
    }

    public function create_view()
    {
        $view = new View();
        $view->set('label', __('Inline Editing', 'codepress-admin-columns'))
             ->set('instructions', $this->get_instruction())
             ->set('setting', $this->create_toggle_element());

        foreach ($this->sections as $section) {
            $view->set('sections', [$section->create_view()]);
        }

        return $view;
    }

    /**
     * @return string
     */
    public function get_edit()
    {
        return $this->edit;
    }

    /**
     * @param string $edit
     *
     * @return $this
     */
    public function set_edit($edit)
    {
        $this->edit = $edit;

        return $this;
    }

    public function is_active()
    {
        return 'on' === $this->get_edit();
    }

}