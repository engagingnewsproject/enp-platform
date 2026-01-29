<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreenRepository\Rules;
use AC\ListScreenRepository\Storage;
use ACP\ConditionalFormat;
use ACP\ListScreenRepository;
use ACP\Search\SegmentRepository;
use ACP\Storage\AbstractDecoderFactory;
use ACP\Storage\Directory;
use ACP\Storage\EncoderFactory;
use ACP\Storage\Serializer;
use InvalidArgumentException;

final class FileFactory implements Storage\ListScreenRepositoryFactory
{

    private EncoderFactory $encoder_factory;

    private AbstractDecoderFactory $decoder_factory;

    private Serializer\PhpSerializer\File $serializer;

    private Serializer\PhpSerializer\I18nFactory $i18n_serializer_factory;

    private SegmentRepository\FileFactory $segment_file_factory;

    private ConditionalFormat\RulesRepository\FileFactory $rules_file_factory;

    public function __construct(
        EncoderFactory $encoder_factory,
        AbstractDecoderFactory $decoder_factory,
        Serializer\PhpSerializer\File $serializer,
        Serializer\PhpSerializer\I18nFactory $i18n_serializer_factory,
        SegmentRepository\FileFactory $segment_file_factory,
        ConditionalFormat\RulesRepository\FileFactory $rules_file_factory
    ) {
        $this->encoder_factory = $encoder_factory;
        $this->decoder_factory = $decoder_factory;
        $this->serializer = $serializer;
        $this->i18n_serializer_factory = $i18n_serializer_factory;
        $this->segment_file_factory = $segment_file_factory;
        $this->rules_file_factory = $rules_file_factory;
    }

    public function create(
        string $path,
        bool $writable,
        ?Rules $rules = null,
        ?string $i18n_text_domain = null
    ): Storage\ListScreenRepository {
        if ($path === '') {
            throw new InvalidArgumentException('Invalid path.');
        }

        $serializer = $this->serializer;

        if ($i18n_text_domain) {
            $serializer = $this->i18n_serializer_factory->create($serializer, $i18n_text_domain);
        }

        $directory = new Directory($path);

        $file = new ListScreenRepository\CachedFile(
            new ListScreenRepository\File(
                $directory,
                $this->decoder_factory,
                $this->encoder_factory,
                $serializer,
                new SegmentHandler(
                    $this->segment_file_factory->create(
                        $directory,
                        $this->decoder_factory,
                        $this->encoder_factory,
                        $this->serializer,
                    ),
                ),
                new ConditionalFormatHandler(
                    $this->rules_file_factory->create(
                        $directory,
                        $this->serializer
                    )
                ),
            )
        );

        return new Storage\ListScreenRepository($file, $writable, $rules);
    }

}