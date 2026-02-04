<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnIterator;
use AC\ColumnIterator\ProxyColumnIterator;
use AC\ColumnRepository\EncodedData;
use AC\ListScreen;
use AC\Plugin\Version;
use AC\Setting\ConfigCollection;
use AC\TableScreen;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use ACP\Exception\NonDecodableDataException;
use ACP\Search\Entity\Segment;
use ACP\Search\SegmentCollection;
use ACP\Search\Type\SegmentKey;
use DateTime;

class Version630 extends Version510 implements SegmentsDecoder
{

    public const VERSION = '6.3';
    public const SEGMENTS = 'segments';

    protected function get_version(): Version
    {
        return new Version(self::VERSION);
    }

    protected function extract_list_screen_id(array $encoded_data): string
    {
        return $encoded_data['list_screen']['id'] ?? '';
    }

    protected function extract_table_id(array $encoded_data): string
    {
        return $encoded_data['list_screen']['type'] ?? '';
    }

    public function has_segments(): bool
    {
        $segments = $this->encoded_data[self::SEGMENTS] ?? null;

        return $segments && is_array($segments);
    }

    public function get_segments(): SegmentCollection
    {
        if ( ! $this->has_segments()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $segments = [];

        foreach ($this->encoded_data[self::SEGMENTS] as $encoded_segment) {
            // Backwards compatibility for segments that have not stored their creation date
            $date_created = isset($encoded_segment['date_created'])
                ? DateTime::createFromFormat('U', (string)$encoded_segment['date_created'])
                : new DateTime();

            $segments[] = new Segment(
                new SegmentKey($encoded_segment['key']),
                $encoded_segment['name'],
                $encoded_segment['url_parameters'],
                new ListScreenId($encoded_segment['list_screen_id']),
                null,
                $date_created
            );
        }

        return new SegmentCollection($segments);
    }

    public function get_list_screen(): ListScreen
    {
        $this->assert_has_list_screen();

        $data = $this->encoded_data['list_screen'];
        $table_screen = $this->table_screen_factory->create($this->get_table_id());

        $list_screen = new ListScreen(
            $this->get_list_screen_id(),
            $data['title'] ?? '',
            $table_screen,
            $this->create_column_iterator($table_screen, $data['columns'] ?? []),
            $data['settings'] ?? [],
            new ListScreenStatus($data['status'] ?? null),
            DateTime::createFromFormat('U', (string)$data['updated'])
        );

        if ($this->has_segments()) {
            $list_screen->set_segments($this->get_segments());
        }

        return $list_screen;
    }

    private function create_column_iterator(TableScreen $table_screen, array $encoded_columns): ColumnIterator
    {
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