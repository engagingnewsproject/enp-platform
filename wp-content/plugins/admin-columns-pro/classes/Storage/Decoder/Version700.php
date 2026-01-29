<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use AC\ColumnFactories\Aggregate;
use AC\ListScreen;
use AC\Plugin\Version;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use AC\Type\ListScreenId;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\RulesCollection;
use ACP\Exception\NonDecodableDataException;
use ACP\Search\Entity\Segment;
use ACP\Search\SegmentCollection;
use ACP\Search\SegmentSchema;
use ACP\Search\Type\SegmentKey;
use DateTime;

final class Version700 extends Version630 implements ConditionalFormatDecoder
{

    public const VERSION = '7.0';
    public const CONDITIONAL_FORMAT = 'conditional_format';

    private ConditionalFormat\Decoder $conditional_format_decoder;

    public function __construct(
        array $encoded_data,
        VersionCompatibility $version_compatibility,
        ConditionalFormat\Decoder $conditional_format_decoder,
        TableScreenFactory $table_screen_factory,
        Aggregate $column_factory,
        OriginalColumnsRepository $original_columns_repository
    ) {
        parent::__construct(
            $encoded_data,
            $version_compatibility,
            $table_screen_factory,
            $column_factory,
            $original_columns_repository
        );

        $this->conditional_format_decoder = $conditional_format_decoder;
    }

    protected function get_version(): Version
    {
        return new Version(self::VERSION);
    }

    public function get_segments(): SegmentCollection
    {
        if ( ! $this->has_segments()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $segments = [];

        foreach ($this->encoded_data[self::SEGMENTS] as $encoded_segment) {
            $segments[] = new Segment(
                new SegmentKey($encoded_segment[SegmentSchema::KEY]),
                $encoded_segment[SegmentSchema::NAME],
                $encoded_segment[SegmentSchema::URL_PARAMETERS],
                new ListScreenId($encoded_segment[SegmentSchema::LIST_SCREEN_ID]),
                null,
                DateTime::createFromFormat('U', $encoded_segment[SegmentSchema::DATE_CREATED])
            );
        }

        return new SegmentCollection($segments);
    }

    public function get_list_screen(): ListScreen
    {
        $list_screen = parent::get_list_screen();

        if ($this->has_conditional_formatting()) {
            $list_screen->set_conditional_format($this->get_conditional_formatting());
        }

        return $list_screen;
    }

    public function has_conditional_formatting(): bool
    {
        $rules = $this->encoded_data[self::CONDITIONAL_FORMAT] ?? null;

        return $rules && is_array($rules);
    }

    public function get_conditional_formatting(): RulesCollection
    {
        if ( ! $this->has_conditional_formatting()) {
            throw new NonDecodableDataException($this->encoded_data);
        }

        $collection = new RulesCollection();

        foreach ($this->encoded_data[self::CONDITIONAL_FORMAT] as $rules) {
            $collection->add($this->conditional_format_decoder->decode($rules));
        }

        return $collection;
    }

}