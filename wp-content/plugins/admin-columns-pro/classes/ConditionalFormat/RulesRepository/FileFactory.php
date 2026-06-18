<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RulesRepository;

use ACP\ConditionalFormat\Decoder;
use ACP\ConditionalFormat\Encoder;
use ACP\Storage\Directory;
use ACP\Storage\Serializer;

final class FileFactory
{

    private Encoder $encoder;

    private Decoder $decoder;

    private Database $database_rules_repository;

    public function __construct(
        Encoder $encoder,
        Decoder $decoder,
        Database $database_rules_repository
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->database_rules_repository = $database_rules_repository;
    }

    public function create(Directory $directory, Serializer $serializer): File
    {
        return new File(
            $this->encoder,
            $this->decoder,
            $this->database_rules_repository,
            $directory,
            $serializer,
        );
    }

}