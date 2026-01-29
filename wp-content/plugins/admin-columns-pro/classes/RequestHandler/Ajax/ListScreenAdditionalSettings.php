<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\ListScreenRepository\Sort\ManualOrder;
use AC\ListScreenRepository\Storage;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response\Json;
use AC\TableScreen;
use AC\TableScreenFactory\Aggregate;
use AC\Type\TableId;
use ACP\Admin\Encoder;
use ACP\ListScreenRepository\TemplateJsonFile;
use ACP\Query\QueryRegistry;
use ACP\Search\TableMarkupFactory;
use ACP\Settings\ListScreen\TableElementsFactory;
use ACP\TableScreen\RelatedRepository;

class ListScreenAdditionalSettings implements RequestAjaxHandler
{

    private Storage $storage;

    private Aggregate $table_screen_factory;

    private TableElementsFactory $table_elements_factory;

    private RelatedRepository\Aggregate $related_repository;

    private Nonce\Ajax $nonce;

    private TemplateJsonFile $template_storage;

    public function __construct(
        Aggregate $table_screen_factory,
        Storage $storage,
        TableElementsFactory $table_elements_factory,
        RelatedRepository\Aggregate $related_repository,
        Nonce\Ajax $nonce,
        TemplateJsonFile $template_storage
    ) {
        $this->table_screen_factory = $table_screen_factory;
        $this->storage = $storage;
        $this->table_elements_factory = $table_elements_factory;
        $this->related_repository = $related_repository;
        $this->nonce = $nonce;
        $this->template_storage = $template_storage;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Json();

        if ( ! $this->nonce->verify($request)) {
            $response->error();
        }

        $table_screen = $this->table_screen_factory->create(
            new TableId((string)$request->get('list_key'))
        );

        wp_send_json_success([
            'table_views'    => $this->get_table_views($table_screen->get_id()),
            'templates'      => $this->get_templates($table_screen->get_id()),
            'table_elements' => $this->get_table_elements($table_screen),
            'related_tables' => $this->get_related_tables($table_screen->get_id()),
            'can_sort'       => $this->supports_sorting($table_screen),
            'can_search'     => $this->supports_search($table_screen),
        ]);
    }

    private function get_related_tables(TableId $table_id): array
    {
        $related = [];

        foreach ($this->related_repository->find_all($table_id) as $table_screen) {
            $related[] = [
                'value' => (string)$table_screen->get_id(),
                'label' => (string)$table_screen->get_labels(),
            ];
        }

        return $related;
    }

    private function supports_search(TableScreen $table_screen): bool
    {
        return null !== TableMarkupFactory::get_table_markup_reference($table_screen);
    }

    private function supports_sorting(TableScreen $table_screen): bool
    {
        return QueryRegistry::can_create($table_screen);
    }

    private function get_table_elements(TableScreen $table_screen): array
    {
        $elements = [];

        foreach ($this->table_elements_factory->create($table_screen)->all() as $table_element) {
            $elements[] = [
                'name'         => $table_element->get_name(),
                'label'        => $table_element->get_label(),
                'group'        => $table_element->get_group(),
                'default'      => $table_element->is_enabled_by_default(),
                'dependent_on' => $table_element->has_dependent_on() ? $table_element->get_dependent_on() : null,
            ];
        }

        return $elements;
    }

    private function get_templates(TableId $table_id): array
    {
        $table_views = [];

        foreach ($this->template_storage->find_all_by_table_id($table_id, new ManualOrder()) as $list_screen) {
            $table_views[] = (new Encoder($list_screen))->encode();
        }

        return $table_views;
    }

    private function get_table_views(TableId $table_id): array
    {
        $table_views = [];

        foreach ($this->storage->find_all_by_table_id($table_id, new ManualOrder()) as $list_screen) {
            $table_views[] = (new Encoder($list_screen))->encode();
        }

        return $table_views;
    }

}