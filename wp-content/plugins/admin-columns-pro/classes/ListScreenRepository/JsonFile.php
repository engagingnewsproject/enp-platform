<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use ACP\Exception\UnserializeException;
use ACP\Storage\CompositeDecoderFactory;
use ACP\Storage\Decoder;
use ACP\Storage\Decoder\SegmentsDecoder;
use ACP\Storage\EncodedContext;
use ACP\Storage\FileIterator;
use ACP\Storage\Unserializer\JsonUnserializer;
use Generator;

abstract class JsonFile extends SourceAwareEncoded
{

    private JsonUnserializer $json_unserializer;

    public function __construct(
        CompositeDecoderFactory $decoder_factory,
        JsonUnserializer $json_unserializer
    ) {
        parent::__construct($decoder_factory);

        $this->json_unserializer = $json_unserializer;
    }

    abstract protected function get_files(): FileIterator;

    protected function get_encoded_contexts(): Generator
    {
        foreach ($this->get_files() as $file) {
            $serialized_data = file_get_contents($file->getRealPath());

            try {
                $encoded_screens = $this->json_unserializer->unserialize($serialized_data);
            } catch (UnserializeException $e) {
                continue;
            }

            foreach ($encoded_screens as $encoded_screen) {
                yield (new EncodedContext($encoded_screen))
                    ->with_attribute(self::SOURCE_ATTRIBUTE, $file->getRealPath());
            }
        }
    }

    protected function create_list_screen(Decoder $decoder): ListScreen
    {
        $list_screen = parent::create_list_screen($decoder);
        $list_screen->set_read_only(true);

        if ($decoder instanceof SegmentsDecoder && $decoder->has_segments()) {
            $list_screen->set_segments($decoder->get_segments());
        }

        return $list_screen;
    }

}