<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use AC\ListScreenCollection;
use AC\ListScreenRepository\Base;
use AC\ListScreenRepository\Filter;
use AC\ListScreenRepository\Sort;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;
use ACP\Exception\DecoderNotFoundException;
use ACP\Storage\CompositeDecoderFactory;
use ACP\Storage\Decoder;
use ACP\Storage\EncodedContext;
use Generator;

abstract class Encoded extends Base
{

    protected const UPDATE_CACHE = true;

    protected CompositeDecoderFactory $decoder_factory;

    /**
     * @var Decoder[]
     */
    protected ?array $decoders = null;

    public function __construct(
        CompositeDecoderFactory $decoder_factory
    ) {
        $this->decoder_factory = $decoder_factory;
    }

    public function find(ListScreenId $id): ?ListScreen
    {
        $this->check_decoders();

        foreach ($this->decoders as $decoder) {
            if ($decoder->get_list_screen_id()->equals($id)) {
                return $this->create_list_screen($decoder);
            }
        }

        return null;
    }

    public function find_all(?Sort $sort = null): ListScreenCollection
    {
        $this->check_decoders();

        $collection = new ListScreenCollection();

        foreach ($this->decoders as $decoder) {
            $collection->add($this->create_list_screen($decoder));
        }

        return $this->sort($collection, $sort);
    }

    public function find_all_by_table_id(
        TableId $table_id,
        ?Sort $sort = null,
        ?ListScreenStatus $status = null
    ): ListScreenCollection {
        $this->check_decoders();

        $collection = new ListScreenCollection();

        foreach ($this->decoders as $decoder) {
            if ($decoder->get_table_id()->equals($table_id)) {
                $collection->add($this->create_list_screen($decoder));
            }
        }

        if ($status) {
            $collection = (new Filter\ListScreenStatus($status))->filter($collection);
        }

        return $sort
            ? $sort->sort($collection)
            : $collection;
    }

    protected function create_list_screen(Decoder $decoder): ListScreen
    {
        return $decoder->get_list_screen();
    }

    /**
     * @return Generator<EncodedContext>
     */
    abstract protected function get_encoded_contexts(): Generator;

    protected function check_decoders(bool $update_cache = false): void
    {
        if ($this->decoders !== null && ! $update_cache) {
            return;
        }

        $this->reset_decoders();

        foreach ($this->get_encoded_contexts() as $context) {
            try {
                $decoder = $this->decoder_factory->create($context->get_encoded_data());
            } catch (DecoderNotFoundException $e) {
                continue;
            }

            if ( ! $decoder->has_list_screen()) {
                continue;
            }

            $this->add_decoder($decoder, $context);
        }
    }

    protected function reset_decoders(): void
    {
        $this->decoders = [];
    }

    protected function add_decoder(Decoder $decoder, EncodedContext $context): void
    {
        $this->decoders[] = $decoder;
    }

}