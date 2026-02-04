<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreenCollection;
use AC\ListScreenRepository;
use AC\ListScreenRepository\ListScreenRepositoryTrait;
use ACP\Exception\DecoderNotFoundException;
use ACP\Exception\UnserializeException;
use ACP\Storage\AbstractDecoderFactory;
use ACP\Storage\Decoder\SegmentsDecoder;
use ACP\Storage\FileIterator;
use ACP\Storage\Unserializer\JsonUnserializer;

abstract class JsonFile implements ListScreenRepository, SourceAware
{

    use ListScreenRepositoryTrait;
    use FilteredListScreenRepositoryTrait;

    private AbstractDecoderFactory $decoder_factory;

    private JsonUnserializer $json_unserializer;

    private ?ListScreenCollection $list_screens = null;

    private ?SourceCollection $sources = null;

    public function __construct(
        AbstractDecoderFactory $decoder_factory,
        JsonUnserializer $json_unserializer
    ) {
        $this->decoder_factory = $decoder_factory;
        $this->json_unserializer = $json_unserializer;
    }

    abstract protected function get_files(): FileIterator;

    public function get_sources(): SourceCollection
    {
        if (null === $this->sources) {
            $this->parse_files();
        }

        return $this->sources;
    }

    protected function find_all_from_source(): ListScreenCollection
    {
        if (null === $this->list_screens) {
            $this->parse_files();
        }

        return $this->list_screens;
    }

    private function parse_files(): void
    {
        $this->list_screens = new ListScreenCollection();
        $this->sources = new SourceCollection();

        foreach ($this->get_files() as $file) {
            $serialized_data = file_get_contents($file->getRealPath());

            try {
                $encoded_screens = $this->json_unserializer->unserialize($serialized_data);
            } catch (UnserializeException $e) {
                continue;
            }

            foreach ($encoded_screens as $encoded_screen) {
                try {
                    $decoder = $this->decoder_factory->create($encoded_screen);
                } catch (DecoderNotFoundException $e) {
                    continue;
                }

                if ( ! $decoder->has_list_screen()) {
                    continue;
                }

                $list_screen = $decoder->get_list_screen();

                if ($decoder instanceof SegmentsDecoder && $decoder->has_segments()) {
                    $list_screen->set_segments($decoder->get_segments());
                }

                $list_screen->set_read_only(true);

                $this->list_screens->add($list_screen);
                $this->sources->add($list_screen->get_id(), $file->getRealPath());
            }
        }
    }

}