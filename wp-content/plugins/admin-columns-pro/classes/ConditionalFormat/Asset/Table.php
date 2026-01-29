<?php

namespace ACP\ConditionalFormat\Asset;

use AC\Asset\Location\Absolute;
use AC\Asset\Script;
use AC\Capabilities;
use AC\Table\AdminHeadStyle;
use AC\Type\Url\Documentation;
use AC\View;
use ACP\ConditionalFormat\Operators;
use ACP\ConditionalFormat\Type\Key;

final class Table extends Script
{

    private Absolute $root_location;

    private Operators $operators;

    private array $columns;

    private ?Key $applied_rules;

    public function __construct(
        Absolute $location,
        Operators $operators,
        array $columns,
        ?Key $applied_rules = null
    ) {
        parent::__construct(
            'acp-cf-table',
            $location->with_suffix('assets/conditional-format/js/table.js'),
            ['ac-table']
        );

        $this->root_location = $location;
        $this->operators = $operators;
        $this->columns = $columns;
        $this->applied_rules = $applied_rules;
    }

    public function register(): void
    {
        parent::register();

        $styles = $this->get_color_styles();

        $this->add_inline_variable('acp_cf_settings', [
            'operators'         => array_values($this->operators->get_operators()),
            'columns'           => $this->columns,
            'applied_rules'     => $this->applied_rules instanceof Key ? (string)$this->applied_rules : null,
            'format_styles'     => $styles,
            'can_manage_global' => current_user_can(Capabilities::MANAGE),
        ]);

        $view = (new View(['styles' => $styles]))->set_template('conditional-formatting/styles');

        AdminHeadStyle::add($view->render());

        wp_localize_script($this->get_handle(), 'acp_cf_settings_i18n', [
            'scope_global'           => __('Everyone', 'codepress-admin-columns'),
            'scope_personal'         => __('Personal', 'codepress-admin-columns'),
            'scope'                  => __('Select Rules', 'codepress-admin-columns'),
            'scope_applied_global'   => __('Visible to all users.', 'codepress-admin-columns'),
            'scope_applied_personal' => __('Visible only to you.', 'codepress-admin-columns'),
            'no_rules'               => __(
                'Add your first rule to start using conditional formatting.',
                'codepress-admin-columns'
            ),
            'no_rules_copy_singular' => __('Copy %d rule from %s.', 'codepress-admin-columns'),
            'no_rules_copy_plural'   => __('Copy %d rules from %s.', 'codepress-admin-columns'),
            'reset'                  => __('Reset', 'codepress-admin-columns'),
            'between_and'            => _x('and', 'between_operator', 'codepress-admin-columns'),
            'apply'                  => __('Apply', 'codepress-admin-columns'),
            'save_apply'             => __('Save &amp; Apply', 'codepress-admin-columns'),
            'cancel'                 => __('Cancel', 'codepress-admin-columns'),
            'add_rule'               => __('Add Rule', 'codepress-admin-columns'),
            'add_another_condition'  => __('Add condition', 'codepress-admin-columns'),
            'conditional_formatting' => __('Conditional Formatting', 'codepress-admin-columns'),
            'formatting'             => __('Conditional Formatting', 'codepress-admin-columns'),
            'formatting_style'       => __('Formatting Style', 'codepress-admin-columns'),
            'documentation_link'     => $this->get_documentation_link(),
        ]);
    }

    private function get_color_styles(): array
    {
        $location = $this->root_location->with_suffix('config/color_styles.php');

        return (array)apply_filters('acp/conditional_format/formats', require $location->get_path());
    }

    private function get_documentation_link(): string
    {
        return sprintf(
            '<a href="%s" class="ac-external" target="_blank">%s</a><span class="dashicons dashicons-external"></span>',
            Documentation::create_with_path(Documentation::ARTICLE_CONDITIONAL_FORMATTING),
            __('Documentation', 'codepress-admin-columns')
        );
    }

}