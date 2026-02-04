<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\ColumnIterator;
use AC\ColumnIterator\ProxyColumnIterator;
use AC\ColumnRepository\EncodedData;
use AC\ListScreen;
use AC\Plugin\Version;
use AC\Setting\ConfigCollection;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;
use ACP\Exception\NonDecodableDataException;
use ACP\Storage\Decoder;
use DateTime;

class Version510 implements Decoder
{

    public const VERSION = '5.1';

    protected array $encoded_data;

    protected TableScreenFactory $table_screen_factory;

    protected Aggregate $column_factory;

    protected OriginalColumnsRepository $original_columns_repository;

    protected ?ListScreenId $list_screen_id = null;

    protected ?TableId $table_id = null;

    public function __construct(
        array $encoded_data,
        VersionCompatibility $version_compatibility,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository
    ) {
        $version_compatibility->assert($encoded_data, $this->get_version());

        $this->encoded_data = $encoded_data;
        $this->table_screen_factory = $table_screen_factory;
        $this->column_factory = $column_factory;
        $this->original_columns_repository = $original_columns_repository;

        $list_screen_id = $this->extract_list_screen_id($encoded_data);

        if (ListScreenId::is_valid_id($list_screen_id)) {
            $this->list_screen_id = new ListScreenId($list_screen_id);
        }

        $table_id = $this->extract_table_id($encoded_data);

        if (TableId::validate($table_id)) {
            $this->table_id = new TableId($table_id);
        }
    }

    protected function get_version(): Version
    {
        return new Version(self::VERSION);
    }

    protected function extract_list_screen_id(array $encoded_data): string
    {
        return $encoded_data['id'] ?? '';
    }

    protected function extract_table_id(array $encoded_data): string
    {
        return $encoded_data['type'] ?? '';
    }

    protected function assert_has_list_screen(): void
    {
        if ( ! $this->has_list_screen()) {
            throw new NonDecodableDataException($this->encoded_data);
        }
    }

    public function has_list_screen(): bool
    {
        return $this->list_screen_id !== null &&
               $this->table_id !== null &&
               $this->table_screen_factory->can_create($this->table_id);
    }

    public function get_list_screen_id(): ListScreenId
    {
        $this->assert_has_list_screen();

        return $this->list_screen_id;
    }

    public function get_table_id(): TableId
    {
        $this->assert_has_list_screen();

        return $this->table_id;
    }

    public function get_list_screen(): ListScreen
    {
        $this->assert_has_list_screen();

        $table_screen = $this->table_screen_factory->create($this->table_id);

        return new ListScreen(
            $this->list_screen_id,
            $this->encoded_data['title'] ?? '',
            $table_screen,
            $this->create_column_iterator($table_screen, $this->encoded_data['columns'] ?? []),
            $this->encoded_data['settings'] ?? [],
            new ListScreenStatus($this->encoded_data['status'] ?? null),
            DateTime::createFromFormat('U', (string)$this->encoded_data['updated'])
        );
    }

    private function create_column_iterator(TableScreen $table_screen, array $encoded_columns): ColumnIterator
    {
        foreach ($encoded_columns as $name => $encoded_column) {
            // Older decoders did not set the `name` property
            if (empty($encoded_column['name'])) {
                $encoded_columns[$name]['name'] = $name;
            }
        }

        return new ProxyColumnIterator(
            new EncodedData(
                $this->column_factory->create($table_screen),
                ConfigCollection::create_from_array($encoded_columns),
                $this->original_columns_repository,
                $table_screen
            )
        );
    }

}