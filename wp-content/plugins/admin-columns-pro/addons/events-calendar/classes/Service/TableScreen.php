<?php

declare(strict_types=1);

namespace ACA\EC\Service;

use AC;
use AC\Asset\Location;
use AC\Asset\Style;
use AC\Registerable;
use ACA\EC\Sorting;
use ACP\Sorting\ModelFactory;
use WP_Query;

class TableScreen implements Registerable
{

    private array $notices;

    private array $filter_vars;

    private Location\Absolute $location;

    private ModelFactory $model_factory;

    public function __construct(Location\Absolute $location, ModelFactory $model_factory)
    {
        $this->location = $location;
        $this->model_factory = $model_factory;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'table_scripts']);
        add_action('ac/table/screen', [$this, 'add_events_filter_vars']);
        add_action('ac/table/list_screen', [$this, 'register_event_sorting_fix']);
        add_action('parse_query', [$this, 'parse_query'], 51);
    }

    public function table_scripts(AC\ListScreen $list_screen): void
    {
        if ($this->is_events_table($list_screen->get_table_screen())) {
            return;
        }

        $script = new Style('aca-ec-table', $this->location->with_suffix('assets/css/table.css'));
        $script->enqueue();
    }

    private function is_events_table(AC\TableScreen $table_screen): bool
    {
        return $table_screen instanceof AC\PostType &&
               in_array(
                   (string)$table_screen->get_post_type(),
                   [
                       'tribe_organizer',
                       'tribe_events',
                       'tribe_event_series',
                       'tribe_venue',
                   ],
                   true
               );
    }

    public function parse_query(WP_Query $query): void
    {
        if ('tribe_events' !== $query->get('post_type')) {
            return;
        }

        if ( ! filter_input(INPUT_GET, 'orderby')) {
            return;
        }

        // This prevents the default tribe event query changes
        $query->tribe_is_event = false;
    }

    public function add_events_filter_vars(AC\TableScreen $table_screen): void
    {
        if ( ! $table_screen->get_id()->equals(new AC\Type\TableId('tribe_events'))) {
            return;
        }

        $prefix = 'ac_related_filter_';

        $input = filter_input_array(INPUT_GET, [
            $prefix . 'value'      => FILTER_SANITIZE_NUMBER_INT,
            $prefix . 'post_type'  => FILTER_DEFAULT,
            $prefix . 'date'       => FILTER_DEFAULT,
            $prefix . 'return_url' => FILTER_DEFAULT,
        ]);

        foreach ($input as $k => $v) {
            unset($input[$k]);
            $input[str_replace($prefix, '', $k)] = $v;
        }

        $input = (object)$input;

        switch ($input->post_type ?? '') {
            case 'tribe_venue':
                $this->filter_on_venue($input->value);

                break;
            case 'tribe_organizer':
                $this->filter_on_organizer($input->value);

                break;
            default:
                return; // invalid post type
        }

        $post_type_object = get_post_type_object($input->post_type);

        if ( ! $post_type_object) {
            return;
        }

        switch ($input->date) {
            case 'future':
                $this->filter_on_future_events();
                $date = __('upcoming', 'codepress-admin-columns');

                break;
            case 'past':
                $this->filter_on_past_events();
                $date = __('previous', 'codepress-admin-columns');

                break;
            default:
                $date = __('all', 'codepress-admin-columns');
        }

        // General notice
        $notice[] = sprintf(
            __('Filtering on %s: Showing %s events from %s.', 'codepress-admin-columns'),
            $post_type_object->labels->singular_name,
            $date,
            get_the_title($input->value)
        );

        // Return to related overview link
        $notice[] = sprintf(
            '<a href="%s" class="notice__actionlink">%s</a>',
            esc_url(admin_url('edit.php?' . base64_decode($input->return_url))),
            sprintf(__('Return to %s', 'codepress-admin-columns'), $post_type_object->labels->name)
        );

        // Remove filters and stay on this overview link
        $input_remove = [];

        foreach ($input as $k => $v) {
            $input_remove[$prefix . $k] = false;
        }

        $notice[] = sprintf(
            '<a href="%s" class="notice-aca-ec-filter__dismiss notice__actionlink">%s</a>',
            add_query_arg($input_remove),
            __('Remove this filter', 'codepress-admin-columns')
        );

        $this->notices[] = [
            'type'   => 'success',
            'notice' => implode(' ', $notice),
        ];

        add_action('admin_notices', [$this, 'display_notices']);
        add_action('pre_get_posts', [$this, 'events_query_callback']);
    }

    public function events_query_callback(WP_Query $wp_query): void
    {
        if ( ! $wp_query->is_main_query()) {
            return;
        }

        $wp_query->query_vars = array_merge($wp_query->query_vars, $this->filter_vars);
    }

    public function display_notices(): void
    {
        foreach ($this->notices as $notice) : ?>
			<div class="notice notice-<?php
            echo $notice['type']; ?> notice-aca-ec-filter">
				<div class="info">
					<p><?php
                        echo $notice['notice']; ?></p>
				</div>
			</div>
        <?php
        endforeach;
    }

    private function filter_on_venue($value): void
    {
        $this->filter_vars['meta_query'][] = [
            'key'   => '_EventVenueID',
            'value' => $value,
        ];
    }

    private function filter_on_organizer($value): void
    {
        $this->filter_vars['meta_query'][] = [
            'key'   => '_EventOrganizerID',
            'value' => $value,
        ];
    }

    private function filter_on_past_events(): void
    {
        $this->filter_vars['meta_query'][] = [
            'key'     => '_EventEndDate',
            'value'   => date('Y-m-d H:i'),
            'compare' => '<',
        ];
    }

    private function filter_on_future_events(): void
    {
        $this->filter_vars['meta_query'][] = [
            'key'     => '_EventStartDate',
            'value'   => date('Y-m-d H:i'),
            'compare' => '>',
        ];
    }

    public function register_event_sorting_fix(AC\ListScreen $list_screen): void
    {
        if ($list_screen->get_table_id()->equals(new AC\Type\TableId('tribe_events'))) {
            $service = new Sorting\EventSortingFix($list_screen, $this->model_factory);
            $service->register();
        }
    }

}