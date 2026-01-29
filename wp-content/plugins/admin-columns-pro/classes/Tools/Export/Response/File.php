<?php

declare(strict_types=1);

namespace ACP\Tools\Export\Response;

use AC\ListScreenCollection;
use ACP\Storage\Encoder;
use ACP\Storage\EncoderFactory;
use ACP\Storage\Serializer\JsonSerializer;
use ACP\Tools\Export\Response;
use ACP\Tools\MessageTrait;

final class File implements Response
{

    use MessageTrait;

    private ListScreenCollection $list_screens;

    private EncoderFactory $encoder_factory;

    private JsonSerializer $json_serializer;

    public function __construct(
        ListScreenCollection $list_screens,
        EncoderFactory $encoder_factory,
        JsonSerializer $json_serializer
    ) {
        $this->list_screens = $list_screens;
        $this->encoder_factory = $encoder_factory;
        $this->json_serializer = $json_serializer;
    }

    public function send(): void
    {
        if ( ! $this->list_screens->count()) {
            $this->set_message(__('No screens selected for export.', 'codepress-admin-columns'));

            return;
        }

        $output = [];

        foreach ($this->list_screens as $list_screen) {
            $encoder = $this->encoder_factory->create();

            if ( ! $encoder instanceof Encoder) {
                continue;
            }

            $output[] = $encoder->set_list_screen($list_screen)
                                ->set_segments($list_screen->get_segments())
                                ->set_conditional_format($list_screen->get_conditional_format())
                                ->encode();
        }

        $headers = [
            'content-disposition' => 'attachment; filename="' . $this->get_file_name() . '"',
            'content-type'        => 'application/json',
        ];

        foreach ($headers as $header => $value) {
            header($header . ': ' . $value);
        }

        echo $this->json_serializer->serialize($output);

        exit;
    }

    private function get_file_name(): string
    {
        return sprintf(
            '%s-%s.%s',
            'admin-columns-export',
            date('Y-m-d-Hi'),
            'json'
        );
    }

}