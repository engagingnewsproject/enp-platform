<?php

namespace ACP\Table;

use AC;
use AC\Asset;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Preferences\SiteFactory;
use AC\Registerable;
use AC\Type\ListScreenId;
use ACP\AdminColumnsPro;

class HorizontalScrolling implements Registerable
{

    private Storage $storage;

    private Asset\Location\Absolute $location;

    public function __construct(Storage $storage, AdminColumnsPro $plugin)
    {
        $this->storage = $storage;
        $this->location = $plugin->get_location();
    }

    public function register(): void
    {
        add_action('ac/table', [$this, 'register_screen_option']);
        add_action('ac/table_scripts', [$this, 'scripts']);
        add_filter('ac/table/body_class', [$this, 'add_horizontal_scrollable_class'], 10, 2);
        add_action('wp_ajax_acp_update_table_option_overflow', [$this, 'update_table_option_overflow']);
    }

    public function preferences(): AC\Preferences\Preference
    {
        return (new SiteFactory())->create('show_overflow_table');
    }

    public function update_table_option_overflow(): void
    {
        check_ajax_referer('ac-ajax');

        $list_id = filter_input(INPUT_POST, 'layout');

        if ( ! ListScreenId::is_valid_id($list_id)) {
            wp_send_json_error('Invalid list id.');
        }

        $list_screen = $this->storage->find(new ListScreenId($list_id));

        if ( ! $list_screen || ! $list_screen->is_user_allowed(wp_get_current_user())) {
            wp_send_json_error('Invalid list screen.');
        }

        $this->preferences()->save(
            $list_screen->get_table_id() . $list_screen->get_id(),
            'true' === filter_input(INPUT_POST, 'value')
        );

        wp_send_json_success();
    }

    private function is_overflow_table(ListScreen $list_screen): bool
    {
        $preference = $this->preferences()->find(
            $list_screen->get_table_id() . $list_screen->get_id()
        );

        // Load the list screen preference when user has not yet set their own preference.
        if (null === $preference) {
            $preference = 'on' === $list_screen->get_preference('horizontal_scrolling');
        }

        return (bool)apply_filters('ac/horizontal_scrolling/enable', $preference, $list_screen);
    }

    public function register_screen_option(AC\Table\Screen $table): void
    {
        $list_screen = $table->get_list_screen();

        if ( ! $list_screen) {
            return;
        }

        $check_box = new AC\Form\Element\Checkbox('acp_overflow_list_screen_table');

        $label = __('Horizontal Scrolling', 'codepress-admin-columns');

        if ($this->is_windows_browser()) {
            $label = sprintf('%s (%s)', $label, __('hold down SHIFT key', 'codepress-admin-columns'));
        }

        $check_box->set_id('acp_overflow_list_screen_table')
                  ->set_options([
                      'yes' => $label,
                  ])
                  ->set_value($this->is_overflow_table($list_screen) ? 'yes' : '');

        $table->register_screen_option($check_box);
    }

    private function is_windows_browser(): bool
    {
        if ( ! isset($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }

        return (bool)preg_match('(win|microsoft)', strtolower($_SERVER['HTTP_USER_AGENT']));
    }

    public function scripts(): void
    {
        $script = new Asset\Script(
            'ac-horizontal-scrolling',
            $this->location->with_suffix('assets/core/js/horizontal-scrolling.js')
        );
        $script->enqueue();

        wp_localize_script('ac-horizontal-scrolling', 'acp_horizontal_scrolling', [
            'indicator_enabled' => apply_filters('acp/horizontal_scrolling/show_indicator', true),
        ]);
    }

    public function add_horizontal_scrollable_class($classes, $table)
    {
        $list_screen = $table->get_list_screen();

        if ($list_screen && $this->is_overflow_table($list_screen)) {
            $classes .= ' acp-overflow-table';
        }

        return $classes;
    }

}