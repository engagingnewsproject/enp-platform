<?php

namespace ACP\Search;

use AC;
use AC\Asset\Location;
use AC\ListScreenRepository\Storage;
use AC\Registerable;
use AC\Services;
use AC\Type\TableId;
use ACP\AdminColumnsPro;
use ACP\Search\Type\SegmentKeyGenerator;
use ACP\Settings\ListScreen\TableElements;

final class Addon implements Registerable
{

    use DefaultSegmentTrait;

    private Storage $storage;

    private Location\Absolute $location;

    private Preferences\SmartFiltering $table_preference;

    private Settings\TableElement\SmartFilters $table_element_smart_filters;

    private AC\Request $request;

    private SegmentKeyGenerator $segment_key_generator;

    public function __construct(
        Storage $storage,
        AdminColumnsPro $plugin,
        SegmentRepository\Database $segment_repository,
        AC\Request $request,
        SegmentKeyGenerator $segment_key_generator
    ) {
        $this->storage = $storage;
        $this->location = $plugin->get_location();
        $this->segment_repository = $segment_repository;
        $this->table_preference = new Preferences\SmartFiltering();
        $this->table_element_smart_filters = new Settings\TableElement\SmartFilters();
        $this->request = $request;
        $this->segment_key_generator = $segment_key_generator;
    }

    private function is_active(AC\ListScreen $list_screen): bool
    {
        return (bool)apply_filters(
            'ac/search/enable',
            $this->table_preference->is_active($list_screen->get_table_id()),
            $list_screen
        );
    }

    public function register(): void
    {
        $services = new Services();
        $services->add($this->get_table_screen_options())
                 ->add($this->get_column_settings());
        $services->register();

        add_action('ac/table/list_screen', [$this, 'table_screen_request'], 10, 2);
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
        add_action('ac/table/list_screen', [$this, 'request_setter']);
        add_action('ac/list_screen/deleted', [$this, 'delete_segments_after_list_screen_deleted']);
        add_action('deleted_user', [$this, 'delete_segments_after_user_deleted']);

        add_action('wp_ajax_acp_search_comparison_request', [$this, 'comparison_request']);
        add_action('wp_ajax_acp_search_segment_request', [$this, 'segment_request']);
        add_action('wp_ajax_acp_enable_smart_filtering_button', [$this, 'update_smart_filtering_preference']);
    }

    public function update_smart_filtering_preference(): void
    {
        check_ajax_referer('ac-ajax');

        (new Preferences\SmartFiltering())->set_status(
            new TableId((string)filter_input(INPUT_POST, 'list_screen')),
            'true' === filter_input(INPUT_POST, 'value')
        );
    }

    private function get_column_settings(): Settings
    {
        return new Settings([
            new AC\Asset\Style('acp-search-admin', $this->location->with_suffix('assets/search/css/admin.css')),
        ]);
    }

    private function get_table_screen_options(): TableScreenOptions
    {
        return new TableScreenOptions(
            $this->location,
            $this->table_preference,
            $this->table_element_smart_filters
        );
    }

    public function add_table_elements(TableElements $collection, AC\TableScreen $table_screen): void
    {
        if ( ! TableMarkupFactory::get_table_markup_reference($table_screen)) {
            return;
        }

        $collection->add($this->table_element_smart_filters, 40)
                   ->add(new Settings\TableElement\SavedFilters(), 41);
    }

    public function comparison_request(): void
    {
        check_ajax_referer('ac-ajax');

        $request = new AC\Request();

        $comparison = new RequestHandler\Comparison(
            $this->storage,
            $request
        );

        $comparison->dispatch($request->get('method'));
    }

    public function table_screen_request(AC\ListScreen $list_screen, AC\TableScreen $table_screen): void
    {
        if ( ! $this->is_active($list_screen) ||
             ! TableScreenSupport::is_searchable($table_screen)) {
            return;
        }

        $this->request->add_middleware(new Middleware\Segment($list_screen, $this->segment_repository))
                      ->add_middleware(new Middleware\Request());

        $request_handler = new RequestHandler\Rules($list_screen);
        $request_handler->handle($this->request);

        if ( ! $this->table_element_smart_filters->is_enabled($list_screen)) {
            return;
        }

        $table_factory = new TableScriptFactory($this->location);

        $assets = [
            new AC\Asset\Style('aca-search-table', $this->location->with_suffix('assets/search/css/table.css')),
            $table_factory->create(
                $list_screen,
                $this->request,
                $this->get_default_segment_key($list_screen)
            ),
        ];

        $table_markup = TableMarkupFactory::create(
            $table_screen,
            $assets
        );

        if ($table_markup) {
            $table_markup->register();
        }
    }

    public function request_setter(AC\ListScreen $list_screen): void
    {
        $search_setter = new RequestHandler\RequestSetter(
            $list_screen,
            $this->segment_repository
        );
        $search_setter->handle($this->request);
    }

    public function segment_request(): void
    {
        check_ajax_referer('ac-ajax');

        $controller = new RequestHandler\Segment(
            $this->storage,
            $this->request,
            $this->segment_repository,
            $this->segment_key_generator
        );

        $controller->dispatch($this->request->get('method'));
    }

    public function delete_segments_after_list_screen_deleted(AC\ListScreen $list_screen): void
    {
        $this->segment_repository->delete_all($list_screen->get_id());
    }

    public function delete_segments_after_user_deleted(int $user_id): void
    {
        foreach ($this->segment_repository->find_all_personal($user_id) as $segment) {
            $this->segment_repository->delete($segment->get_key());
        }
    }

}