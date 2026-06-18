<?php

declare(strict_types=1);

namespace ACA\ACF\RequestHandler;

use AC\Capabilities;
use AC\Column\ColumnIdGenerator;
use AC\ColumnCollection;
use AC\ColumnTypeRepository;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response\Json;
use AC\Setting\Config;
use AC\TableScreenFactory;
use AC\Type\EditorUrlFactory;
use AC\Type\ListScreenId;
use AC\Type\ListScreenIdGenerator;
use AC\Type\TableId;
use AC\Type\TableScreenContext;
use ACA\ACF\ColumnFactories;
use ACA\ACF\ColumnMatcher;
use ACA\ACF\Field;
use ACA\ACF\FieldRepository;

class FieldSettingsAddColumn implements RequestAjaxHandler
{

    private Storage $storage;

    private TableScreenFactory $table_screen_factory;

    private ColumnFactories\FieldFactory $column_factory;

    private ColumnTypeRepository $column_type_repository;

    private ListScreenIdGenerator $list_screen_id_generator;

    private ColumnMatcher $column_matcher;

    private FieldRepository $field_repository;

    public function __construct(
        Storage $storage,
        TableScreenFactory $table_screen_factory,
        ColumnFactories\FieldFactory $column_factory,
        ColumnTypeRepository $column_type_repository,
        ListScreenIdGenerator $list_screen_id_generator,
        ColumnMatcher $column_matcher,
        FieldRepository $field_repository
    ) {
        $this->storage = $storage;
        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
        $this->column_type_repository = $column_type_repository;
        $this->list_screen_id_generator = $list_screen_id_generator;
        $this->column_matcher = $column_matcher;
        $this->field_repository = $field_repository;
    }

    private function create_config(Field $field, Request $request): Config
    {
        $config = [
            'name'  => (string)(new ColumnIdGenerator())->generate(),
            'label' => $request->get('field_label', ''),
        ];

        $prepend = $field->get_setting_prepend();
        $append = $field->get_setting_append();

        if ($prepend !== '' || $append !== '') {
            $config['apply_before_after'] = 'on';
            $config['before'] = $prepend;
            $config['after'] = $append;
        }

        if ($field instanceof Field\Type\Repeater) {
            $config['repeater_display'] = 'subfield';
            $config['sub_field'] = (string)$request->get('field_key', '');
        }

        $label = (string)$request->get('label');

        if ($label !== '') {
            $config['label'] = sanitize_text_field(trim($label));
        }

        return new Config($config);
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Json();

        if ( ! (new Nonce\Ajax())->verify($request)) {
            $response->error();
        }

        $table_id = new TableId((string)$request->get('table_id'));
        $field_key = (string)$request->get('field_key');
        $list_screen_id = (string)$request->get('list_screen_id');

        if (empty($field_key)) {
            $response->error();
        }

        if ( ! $this->table_screen_factory->can_create($table_id)) {
            $response->error();
        }

        $table_screen = $this->table_screen_factory->create($table_id);
        $table_context = TableScreenContext::from_table_screen($table_screen);

        if ( ! $table_context) {
            $response->error();
        }

        $field = $this->field_repository->find_by_field_key($field_key);

        if ( ! $field) {
            $response->error();
        }

        $column_factory = $this->column_factory->create($table_context, $field);

        if ( ! $column_factory) {
            $response->error();
        }

        $column = $column_factory->create(
            $this->create_config($field, $request)
        );

        $list_screen = ListScreenId::is_valid_id($list_screen_id)
            ? $this->find_list_screen_by_id($table_id, new ListScreenId($list_screen_id))
            : $this->find_first_writable_list_screen($table_id);

        if ($list_screen) {
            $list_screen->set_columns(
                ColumnCollection::from_iterator($list_screen->get_columns())
                    ->add($column)
            );
        } else {
            $list_screen = new ListScreen(
                $this->list_screen_id_generator->generate(),
                $table_screen->get_labels()->get_singular(),
                $table_screen,
                $this->column_type_repository
                    ->find_all_by_original($table_screen)
                    ->add($column)
            );
        }

        $this->storage->save($list_screen);

        $editor_url = EditorUrlFactory::create($table_id, false, $list_screen->get_id())
            ->with_arg('open_columns', (string)$column->get_id());

        $response->set_parameter('editor_url', (string)$editor_url);
        $response->set_parameter('view_title', $list_screen->get_title());
        $response->success();
    }

    private function find_list_screen_by_id(TableId $table_id, ListScreenId $id): ?ListScreen
    {
        foreach ($this->storage->find_all_by_table_id($table_id) as $list_screen) {
            if ($list_screen->get_id()->equals($id) && ! $list_screen->is_read_only()) {
                return $list_screen;
            }
        }

        return null;
    }

    private function find_first_writable_list_screen(TableId $table_id): ?ListScreen
    {
        foreach ($this->storage->find_all_by_table_id($table_id) as $list_screen) {
            if ( ! $list_screen->is_read_only()) {
                return $list_screen;
            }
        }

        return null;
    }

}
