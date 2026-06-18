<?php

declare(strict_types=1);

namespace ACA\ACF\Service;

use AC;
use AC\Acf\FieldGroupCache;
use AC\Asset\Location\Absolute;
use AC\Asset\Script;
use AC\Asset\Style;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Registerable;
use AC\TableScreen;
use AC\Type\EditorUrlFactory;
use AC\Type\TableId;
use AC\Type\TableScreenContext;
use AC\View;
use ACA\ACF\ColumnFactories\FieldFactory;
use ACA\ACF\ColumnMatcher;
use ACA\ACF\Field;
use ACA\ACF\FieldRepository;
use ACA\ACF\FieldType;
use ACA\ACF\Service\FieldSettings\FieldContext;
use ACA\ACF\Service\FieldSettings\MatchResult;

class FieldSettings implements Registerable
{

    private Storage $storage;

    private FieldGroupCache $field_group_cache;

    private ColumnMatcher $column_matcher;

    private Absolute $location;

    private FieldRepository $field_repository;

    private FieldFactory $column_factory;

    public function __construct(
        Storage $storage,
        FieldGroupCache $field_group_cache,
        ColumnMatcher $column_matcher,
        Absolute $location,
        FieldRepository $field_repository,
        FieldFactory $column_factory
    ) {
        $this->storage = $storage;
        $this->field_group_cache = $field_group_cache;
        $this->column_matcher = $column_matcher;
        $this->location = $location;
        $this->field_repository = $field_repository;
        $this->column_factory = $column_factory;
    }

    public function register(): void
    {
        if ( ! AC\Acf::is_active()) {
            return;
        }

        add_filter('acf/field_group/additional_field_settings_tabs', [$this, 'add_tab']);
        add_action('acf/field_group/render_field_settings_tab/admin_columns', [$this, 'render_tab']);
        add_action('acf/field_group/admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_tab(array $tabs): array
    {
        $tabs['admin_columns'] = __('Admin Columns', 'codepress-admin-columns')
                                 . '<i class="ac-acf-tab-badge"></i>';

        return $tabs;
    }

    public function enqueue_scripts(): void
    {
        $style = new Style('aca-acf-field-settings', $this->location->with_suffix('assets/css/field-settings.css'));
        $style->enqueue();

        $script = new Script('aca-acf-field-settings', $this->location->with_suffix('assets/js/field-settings.js'), ['jquery']);
        $script->enqueue();

        wp_localize_script('aca-acf-field-settings', 'ac_acf_field_settings', [
            'nonce'           => wp_create_nonce('ac-ajax'),
            'adding'          => __('Adding...', 'codepress-admin-columns'),
            'added'           => __('Added', 'codepress-admin-columns'),
            'added_to'        => __('Added to', 'codepress-admin-columns'),
            'added_to_1_view' => sprintf(__('Added to %d view', 'codepress-admin-columns'), 1),
            'views'           => __('views', 'codepress-admin-columns'),
            'add_as_column'   => $this->get_add_column_label(),
            'edit_column'     => __('Edit column →', 'codepress-admin-columns'),
        ]);
    }

    private function render_unsupported_message(array $acf_field, Field $root_field): bool
    {
        $root_type = $root_field->get_type();
        $field_type = $acf_field['type'] ?? null;

        if ($root_field instanceof Field\Type\GroupSubField && in_array($root_type, [FieldType::TYPE_REPEATER, FieldType::TYPE_GROUP], true)) {
            $this->render_tab_hidden();

            return true;
        }

        if (FieldType::TYPE_GROUP === $root_type || (FieldType::TYPE_REPEATER === $root_type && $field_type === 'repeater')) {
            $this->render_message(
                $acf_field,
                sprintf(
                    __('To add columns for this %s, open its sub-fields in the %s tab and use the %s tab on each sub-field.', 'codepress-admin-columns'),
                    $root_type,
                    sprintf('<strong>%s</strong>', __('General', 'codepress-admin-columns')),
                    '<strong>Admin Columns</strong>',
                )
            );

            return true;
        }

        return false;
    }

    public function render_tab(array $acf_field): void
    {
        $group = acf_get_field_group();

        if ( ! $group) {
            return;
        }

        $group_id = (int)($group['ID'] ?? 0);
        $field_key = $acf_field['key'] ?? '';
        $field_type = $acf_field['type'] ?? '';
        $field_name = $acf_field['name'] ?? '';

        if ('' === $field_name) {
            $this->render_message(
                $acf_field,
                __('Enter a Field Name under the General tab to enable adding this field as a column.')
            );

            return;
        }

        if ('new_field' === $field_name) {
            $this->render_message(
                $acf_field,
                __('Admin Columns settings are available after saving.', 'codepress-admin-columns'),
            );

            return;
        }

        $table_screens = $this->field_group_cache->get_table_screens_for_group($group_id);

        if ( ! $table_screens) {
            $this->render_tab_hidden();

            return;
        }

        if (FieldType::TYPE_CLONE === $field_type) {
            $this->render_clone_message($acf_field, $group_id);

            return;
        }

        $field = $this->field_repository->find_by_field_key($field_key);

        if ( ! $field) {
            $this->render_tab_hidden();

            return;
        }

        if ($this->render_unsupported_message($acf_field, $field)) {
            return;
        }

        $columns_found = $this->render_table_screen_rows($table_screens, $acf_field, $field);

        if ($columns_found) {
            $this->render_badge_activation();
        }
    }

    private function get_add_column_label(): string
    {
        return __('Add as column', 'codepress-admin-columns');
    }

    private function render_tab_hidden(): void
    {
        echo '<span class="ac-acf-hide-tab acf-hidden"></span>';
    }

    private function render_badge_activation(): void
    {
        echo '<span class="ac-acf-badge-active acf-hidden"></span>';
    }

    private function render_clone_message(array $field, int $group_id): void
    {
        $message = '<p>'
                   . esc_html__('Clone fields are supported in Admin Columns. To add this field as a column, open the column editor for the relevant list table.', 'codepress-admin-columns')
                   . '</p>';

        $table_screens = $this->field_group_cache->get_table_screens_for_group($group_id);

        if ($table_screens) {
            $table_screen = reset($table_screens);
            $url = EditorUrlFactory::create($table_screen->get_id(), false);

            $message .= sprintf(
                '<p><a href="%s" class="button">%s</a></p>',
                esc_url((string)$url),
                esc_html__('Open column editor', 'codepress-admin-columns')
            );
        }

        acf_render_field_setting(
            $field,
            [
                'label'   => '',
                'type'    => 'message',
                'name'    => 'ac_message',
                'message' => $message,
            ]
        );
    }

    private function render_message(array $field, string $message): void
    {
        acf_render_field_setting(
            $field,
            [
                'label'   => '',
                'type'    => 'message',
                'name'    => 'ac_message',
                'message' => '<p style="color:#50575e;">' . $message . '</p>',
            ]
        );
    }

    private function render_table_screen_rows(array $table_screens, array $acf_field, Field $field): bool
    {
        $rows_html = '';
        $columns_found = false;

        foreach ($table_screens as $table_screen) {
            $table_context = TableScreenContext::from_table_screen($table_screen);

            if ( ! $table_context) {
                continue;
            }

            $factory = $this->column_factory->create($table_context, $field);

            if ( ! $factory) {
                continue;
            }

            [$row_html, $has_columns] = $this->render_table_screen_row(
                $table_context,
                $table_screen,
                $field,
                $acf_field,
                count($table_screens) === 1
            );

            $rows_html .= $row_html;

            if ($has_columns) {
                $columns_found = true;
            }
        }

        if ($rows_html === '') {
            $this->render_message(
                $acf_field,
                __('No matching list tables found for this field group\'s location rules.', 'codepress-admin-columns')
            );

            return false;
        }

        $intro = '<p class="ac-acf-intro">'
                 . esc_html__('Add this field as a column in the list tables below. Once added, you can open that specific column in Admin Columns to configure it.', 'codepress-admin-columns')
                 . '</p>';

        $helper = '<p class="ac-acf-helper">'
                  . esc_html__('Column settings are managed in Admin Columns after creation.', 'codepress-admin-columns')
                  . '</p>';

        acf_render_field_setting(
            $acf_field,
            [
                'label'   => __('Admin Columns', 'codepress-admin-columns'),
                'type'    => 'message',
                'name'    => 'ac_field_settings',
                'message' => $intro . '<div class="ac-acf-list">' . $rows_html . '</div>' . $helper,
            ]
        );

        return $columns_found;
    }

    /**
     * @return array{string, bool}
     */
    private function render_table_screen_row(TableScreenContext $table_context, TableScreen $table_screen, Field $root_field, array $acf_field, bool $start_open): array
    {
        $field_key = $acf_field['key'] ?? '';
        $list_screens = $this->get_writable_list_screens($table_screen->get_id());
        $has_multiple_views = count($list_screens) >= 2;
        $field_label = $root_field->get_label() ?: $root_field->get_meta_key();

        if ($root_field instanceof Field\Type\Repeater) {
            $field_label .= ' ' . ($acf_field['label'] ?? '');
        }

        $field_context = new FieldContext((string)$table_screen->get_id(), $field_key, $field_label);
        $match_result = $this->build_match_result($table_context, $table_screen, $list_screens, $root_field, $field_key);

        $head_html = $this->render_row_head($table_screen, $field_context, $match_result, $has_multiple_views, $start_open);

        $expander_html = $has_multiple_views
            ? $this->render_row_views($list_screens, $field_context, $match_result, $start_open)
            : '';

        $html = sprintf(
            '<div class="ac-acf-row" data-table-id="%s" data-field-key="%s" data-label="%s">'
            . '<div class="ac-acf-row-head">%s</div>'
            . '%s'
            . '</div>',
            esc_attr($field_context->get_table_id()),
            esc_attr($field_context->get_field_key()),
            esc_attr($field_context->get_field_label()),
            $head_html,
            $expander_html
        );

        return [$html, $match_result->get_added_count() > 0];
    }

    // TODO split render_row_head into multiple items ($list_screens) or single_item ($list_screen)
    private function render_row_head(
        TableScreen $table_screen,
        FieldContext $field_context,
        MatchResult $match_result,
        bool $has_multiple_views,
        bool $start_open
    ): string {
        $added_count = $match_result->get_added_count();

        $html = '<div class="ac-acf-row-left">'
                . '<span class="ac-acf-row-name">' . esc_html($table_screen->get_labels()->get_plural()) . '</span>';

        if ($added_count > 0 && $has_multiple_views) {
            $html .= sprintf(
                '<span class="ac-acf-summary">'
                . '<span class="ac-acf-badge">&#10003;</span>'
                . '<span>%s</span>'
                . '</span>',
                esc_html(
                    sprintf(
                        _n('Added to %d view', 'Added to %d views', $added_count, 'codepress-admin-columns'),
                        $added_count
                    )
                )
            );
        } elseif ($added_count > 0) {
            $first_added_title = $match_result->get_first_added_title();

            $html .= sprintf(
                '<span class="ac-acf-status">'
                . '<span class="ac-acf-badge">&#10003;</span>'
                . '<span class="ac-acf-status-text">%s</span>'
                . '</span>',
                esc_html(
                    $first_added_title
                        ? sprintf(__('Added to %s', 'codepress-admin-columns'), $first_added_title)
                        : __('Added', 'codepress-admin-columns')
                )
            );
        }

        if ( ! $has_multiple_views && $match_result->get_first_view_url() && $added_count === 0) {
            $html .= sprintf(
                '<a href="%s" class="ac-acf-link ac-acf-view-link">%s</a>',
                esc_url((string)$match_result->get_first_view_url()),
                esc_html__('Open view →', 'codepress-admin-columns')
            );
        }

        $html .= '</div><div class="ac-acf-row-actions">';

        if ($has_multiple_views && ! $start_open) {
            $html .= sprintf(
                '<button type="button" class="button ac-acf-toggle-views" data-label-show="%s" data-label-hide="%s">%s</button>',
                esc_attr__('Show table views', 'codepress-admin-columns'),
                esc_attr__('Hide table views', 'codepress-admin-columns'),
                esc_html__('Show table views', 'codepress-admin-columns')
            );
        } elseif ( ! $has_multiple_views) {
            if ($match_result->get_first_editor_url()) {
                $html .= sprintf(
                    '<a href="%s" class="ac-acf-link">%s</a>',
                    esc_url((string)$match_result->get_first_editor_url()),
                    esc_html__('Edit column →', 'codepress-admin-columns')
                );
            }

            if ($added_count === 0) {
                $html .= sprintf(
                    '<button type="button" class="button ac-acf-add-column" data-table-id="%s" data-field-key="%s" data-label="%s">%s</button>',
                    esc_attr($field_context->get_table_id()),
                    esc_attr($field_context->get_field_key()),
                    esc_attr($field_context->get_field_label()),
                    esc_html($this->get_add_column_label())
                );
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param ListScreen[] $list_screens
     */
    private function render_row_views(array $list_screens, FieldContext $field_context, MatchResult $match_result, bool $open): string
    {
        $column_matches = $match_result->get_column_matches();
        $base_urls = $match_result->get_base_urls();
        $views_html = '';

        foreach ($list_screens as $list_screen) {
            $list_screen_id = (string)$list_screen->get_id();
            $column = $column_matches[$list_screen_id] ?? null;
            $base_url = $base_urls[$list_screen_id];

            if ($column) {
                $view = new View([
                    'title'      => $list_screen->get_title(),
                    'editor_url' => (string)$base_url->with_arg('open_columns', (string)$column->get_id()),
                ]);
                $views_html .= $view->set_template('tab-view-column')->render();
            } else {
                $view = new View([
                    'table_id'       => $field_context->get_table_id(),
                    'field_key'      => $field_context->get_field_key(),
                    'field_label'    => $field_context->get_field_label(),
                    'list_screen_id' => $list_screen_id,
                    'title'          => $list_screen->get_title(),
                    'view_url'       => (string)$base_url,
                    'add_label'      => $this->get_add_column_label(),
                ]);
                $views_html .= $view->set_template('tab-view-column-add')->render();
            }
        }

        $expander_class = $open ? 'ac-acf-expander' : 'ac-acf-expander ac-acf-expander--closed';

        return '<div class="' . $expander_class . '">' . $views_html . '</div>';
    }

    /**
     * @return ListScreen[]
     */
    private function get_writable_list_screens(TableId $table_id): array
    {
        $list_screens = [];

        foreach ($this->storage->find_all_by_table_id($table_id) as $list_screen) {
            if ( ! $list_screen->is_read_only()) {
                $list_screens[] = $list_screen;
            }
        }

        return $list_screens;
    }

    /**
     * @param ListScreen[] $list_screens
     */
    private function build_match_result(
        TableScreenContext $table_context,
        TableScreen $table_screen,
        array $list_screens,
        Field $root_field,
        string $field_key
    ): MatchResult {
        $added_count = 0;
        $first_editor_url = null;
        $first_added_title = '';
        $first_view_url = null;
        $column_matches = [];
        $base_urls = [];

        foreach ($list_screens as $list_screen) {
            $column = $this->column_matcher->find_column($table_context, $list_screen, $root_field, $field_key);

            $list_screen_id = (string)$list_screen->get_id();
            $base_url = EditorUrlFactory::create($table_screen->get_id(), false, $list_screen->get_id());
            $base_urls[$list_screen_id] = $base_url;

            if ( ! $first_view_url) {
                $first_view_url = $base_url;
            }

            $column_matches[$list_screen_id] = $column;

            if ($column) {
                $added_count++;

                if ( ! $first_editor_url) {
                    $first_editor_url = $base_url->with_arg('open_columns', (string)$column->get_id());
                    $first_added_title = $list_screen->get_title();
                }
            }
        }

        return new MatchResult(
            $added_count,
            $first_editor_url,
            $first_view_url,
            $first_added_title,
            $column_matches,
            $base_urls
        );
    }

}
