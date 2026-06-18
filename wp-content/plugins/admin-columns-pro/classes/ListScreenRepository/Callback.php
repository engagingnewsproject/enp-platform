<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use ACP\Storage\CompositeDecoderFactory;
use ACP\Storage\Decoder;
use ACP\Storage\Decoder\ConditionalFormatDecoder;
use ACP\Storage\Decoder\SegmentsDecoder;
use ACP\Storage\EncodedContext;
use Closure;
use Generator;

final class Callback extends Encoded
{

    private Closure $callback;

    public function __construct(CompositeDecoderFactory $decoder_factory, callable $callback)
    {
        parent::__construct($decoder_factory);

        $this->callback = Closure::fromCallable($callback);
    }

    protected function get_encoded_contexts(): Generator
    {
        $callback = $this->callback;

        foreach ($callback() as $encoded_list_screen) {
            yield new EncodedContext($encoded_list_screen);
        }
    }

    protected function create_list_screen(Decoder $decoder): ListScreen
    {
        $list_screen = parent::create_list_screen($decoder);

        if ($decoder instanceof SegmentsDecoder && $decoder->has_segments()) {
            $list_screen->set_segments($decoder->get_segments());
        }

        if ($decoder instanceof ConditionalFormatDecoder && $decoder->has_conditional_formatting()) {
            $list_screen->set_conditional_format($decoder->get_conditional_formatting());
        }

        return $list_screen;
    }

}