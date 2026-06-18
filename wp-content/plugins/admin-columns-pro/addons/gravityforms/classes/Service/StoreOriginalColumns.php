<?php

declare(strict_types=1);

namespace ACA\GravityForms\Service;

use AC;
use AC\ColumnCollection;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Registerable;
use AC\Storage\Repository\OriginalColumnsRepository;
use ACA\GravityForms\ColumnFactories\EntryFactory;
use ACA\GravityForms\TableScreen;
use GF_Field;
use GFAPI;
use GFFormsModel;

class StoreOriginalColumns implements Registerable
{

    private OriginalColumnsRepository $original_columns_repository;

    private Storage $storage;

    private EntryFactory $column_factory;

    private AC\Type\ListScreenIdGenerator $list_screen_id_generator;

    public function __construct(
        EntryFactory $column_factory,
        OriginalColumnsRepository $original_columns_repository,
        Storage $storage,
        AC\Type\ListScreenIdGenerator $list_screen_id_generator
    ) {
        $this->original_columns_repository = $original_columns_repository;
        $this->storage = $storage;
        $this->column_factory = $column_factory;
        $this->list_screen_id_generator = $list_screen_id_generator;
    }

    public function register(): void
    {
        add_action('ac/table/screen', [$this, 'create_default_list_screen'], 9);
        add_action('ac/table/screen', [$this, 'activate_gf_columns']);
    }

    public function create_default_list_screen(AC\TableScreen $table_screen): void
    {
        if ( ! $table_screen instanceof TableScreen\Entry) {
            return;
        }

        if ( ! apply_filters('ac/gravityforms/create_default_set', true)) {
            return;
        }

        $table_id = $table_screen->get_id();

        if ( ! $this->original_columns_repository->exists($table_id)) {
            return;
        }

        if ($this->storage->find_all_by_table_id($table_id)->count() > 0) {
            return;
        }

        $this->storage->save(
            new ListScreen(
                $this->list_screen_id_generator->generate(),
                __('Default', 'codepress-admin-columns'),
                $table_screen,
                $this->create_columns($table_screen)
            )
        );
    }

    private function create_columns(TableScreen\Entry $table_screen): ColumnCollection
    {
        $collection = new ColumnCollection();

        $columns['is_starred'] = '<span class="dashicons dashicons-star-filled"></span>';

        foreach (GFFormsModel::get_grid_columns($table_screen->get_form_id()) as $field_id => $data) {
            $columns['field_id-' . $field_id] = $data['label'];
        }

        $factories = $this->column_factory->create($table_screen);

        $meta_columns = [
            'field_id-id',
            'field_id-date_created',
            'field_id-ip',
            'field_id-source_url',
            'field_id-payment_status',
            'field_id-transaction_id',
            'field_id-payment_amount',
            'field_id-payment_date',
            'field_id-created_by',
        ];

        foreach ($columns as $type => $label) {
            if (in_array($type, $meta_columns, true)) {
                continue;
            }
            foreach ($factories as $factory) {
                if ($type === $factory->get_column_type()) {
                    $config = new AC\Setting\Config([
                        'name'  => $type,
                        'label' => $label,
                    ]);
                    $collection->add($factory->create($config));
                }
            }
        }

        return $collection;
    }

    public function activate_gf_columns(AC\TableScreen $table_screen): void
    {
        if ( ! $table_screen instanceof TableScreen\Entry) {
            return;
        }

        $form_id = $table_screen->get_form_id();

        if ( ! $form_id) {
            return;
        }

        $table_id = $table_screen->get_id();

        if ( ! $this->original_columns_repository->exists($table_id)) {
            return;
        }

        $form_fields = GFAPI::get_form($form_id)['fields'] ?? [];

        if ( ! $form_fields) {
            return;
        }

        $grid_columns = array_keys(
            GFFormsModel::get_grid_columns($form_id)
        );

        foreach ($grid_columns as $key => $field_id) {
            $field_type = GFAPI::get_field($form_id, $field_id)['type'] ?? null;

            if (in_array($field_type, $this->get_unsupported_field_types())) {
                unset($grid_columns[$key]);
            }
        }

        $current_columns = array_merge(
            $grid_columns,
            $this->get_field_ids($form_fields),
            $this->get_default_table_column_names()
        );

        $current_columns = array_unique($current_columns);

        if (md5(serialize(GFFormsModel::get_grid_column_meta($form_id))) !== md5(serialize($current_columns))) {
            GFFormsModel::update_grid_column_meta($form_id, $current_columns);
        }
    }

    private function get_unsupported_field_types(): array
    {
        return ['section', 'html', 'page'];
    }

    private function get_default_table_column_names(): array
    {
        return [
            'id',
            'date_created',
            'ip',
            'source_url',
            'payment_status',
            'transaction_id',
            'payment_amount',
            'payment_date',
            'created_by',
        ];
    }

    private function get_field_ids(array $form_fields): array
    {
        $field_ids = [];

        foreach ($form_fields as $field) {
            if ( ! $field instanceof GF_Field) {
                continue;
            }

            if (in_array($field->type, $this->get_unsupported_field_types(), true)) {
                continue;
            }

            $inputs = $field->get_entry_inputs();

            $field_ids[] = $field->id;

            if (is_array($inputs)) {
                foreach ($inputs as $input) {
                    $field_ids[] = $input['id'];
                }
            }
        }

        return $field_ids;
    }

}