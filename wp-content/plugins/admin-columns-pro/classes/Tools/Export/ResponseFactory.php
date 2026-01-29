<?php

declare(strict_types=1);

namespace ACP\Tools\Export;

use AC\ListScreenCollection;
use ACP\Storage\EncoderFactory;
use ACP\Storage\Serializer\JsonSerializer;
use ACP\Tools\Export\Response\File;

final class ResponseFactory
{

    private $encoder_factory;

    private $json_serializer;

    public function __construct(
        EncoderFactory $encoder_factory,
        JsonSerializer $json_serializer
    ) {
        $this->encoder_factory = $encoder_factory;
        $this->json_serializer = $json_serializer;
    }

    public function create(ListScreenCollection $list_screens): Response
    {
        return new File(
            $list_screens,
            $this->encoder_factory,
            $this->json_serializer
        );
    }

}