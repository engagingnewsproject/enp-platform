<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use ACP\Storage\Decoder;
use ACP\Storage\EncodedContext;

abstract class SourceAwareEncoded extends Encoded implements SourceAware
{

    protected const SOURCE_ATTRIBUTE = 'source';

    protected ?SourceCollection $sources = null;

    public function get_sources(): SourceCollection
    {
        $this->check_decoders();

        return $this->sources;
    }

    protected function add_decoder(Decoder $decoder, EncodedContext $context): void
    {
        parent::add_decoder($decoder, $context);

        $source = $context->get_attribute(self::SOURCE_ATTRIBUTE);

        if ($source) {
            $this->sources->add($decoder->get_list_screen_id(), $source);
        }
    }

    protected function reset_decoders(): void
    {
        parent::reset_decoders();

        $this->sources = new SourceCollection();
    }

}