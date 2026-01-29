<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use AC\ListScreenCollection;
use AC\ListScreenRepository\ListScreenRepositoryTrait;
use AC\ListScreenRepositoryWritable;
use AC\OpCacheInvalidateTrait;
use ACP\Exception\DecoderNotFoundException;
use ACP\Exception\DirectoryNotWritableException;
use ACP\Exception\FailedToCreateDirectoryException;
use ACP\Exception\FailedToSaveConditionalFormattingException;
use ACP\Exception\FailedToSaveSegmentException;
use ACP\Exception\FileNotWritableException;
use ACP\Storage;
use ACP\Storage\AbstractDecoderFactory;
use ACP\Storage\Directory;
use ACP\Storage\EncoderFactory;
use ACP\Storage\Serializer;
use DirectoryIterator;

final class File implements ListScreenRepositoryWritable, SourceAware, DirectoryAware
{

    use ListScreenRepositoryTrait;
    use FilteredListScreenRepositoryTrait;
    use OpCacheInvalidateTrait;

    private ?ListScreenCollection $list_screens = null;

    private ?SourceCollection $sources = null;

    private Directory $directory;

    private AbstractDecoderFactory $decoder_factory;

    private EncoderFactory $encoder_factory;

    private Serializer $serializer;

    private SegmentHandler $segment_handler;

    private ConditionalFormatHandler $conditional_format_handler;

    public function __construct(
        Directory $directory,
        AbstractDecoderFactory $decoder_factory,
        EncoderFactory $encoder_factory,
        Serializer $serializer,
        SegmentHandler $segment_handler,
        ConditionalFormatHandler $conditional_format_handler
    ) {
        $this->directory = $directory;
        $this->decoder_factory = $decoder_factory;
        $this->encoder_factory = $encoder_factory;
        $this->serializer = $serializer;
        $this->segment_handler = $segment_handler;
        $this->conditional_format_handler = $conditional_format_handler;
    }

    /**
     * @throws FileNotWritableException
     * @throws DirectoryNotWritableException
     * @throws FailedToCreateDirectoryException
     * @throws FailedToSaveSegmentException
     * @throws FailedToSaveConditionalFormattingException
     */
    public function save(ListScreen $list_screen): void
    {
        if ( ! $this->directory->exists()) {
            $this->directory->create();
        }

        if ( ! $this->directory->is_writable()) {
            throw new DirectoryNotWritableException($this->directory->get_path());
        }

        $encoder = $this->encoder_factory
            ->create()
            ->set_list_screen($list_screen);

        $file = sprintf(
            '%s/%s.%s',
            $this->directory->get_path(),
            $list_screen->get_id(),
            $this->get_file_extension()
        );

        $result = file_put_contents(
            $file,
            $this->serializer->serialize($encoder->encode())
        );

        if ($result === false) {
            throw FileNotWritableException::for_file($file);
        }

        $this->opcache_invalidate($file);

        $this->segment_handler->save($list_screen);
        $this->conditional_format_handler->save($list_screen);
    }

    /**
     * @throws FileNotWritableException
     */
    public function delete(ListScreen $list_screen): void
    {
        $id = $list_screen->get_id();

        $this->parse_directory();

        if ( ! $this->sources->contains($id)) {
            throw new FileNotWritableException(sprintf('Could not find %s.', $id));
        }

        $path = $this->sources->get($id);

        $this->opcache_invalidate($path);

        $result = unlink($path);

        if ($result === false) {
            throw FileNotWritableException::for_file($path);
        }

        $this->segment_handler->delete($list_screen);
        $this->conditional_format_handler->delete($list_screen);
    }

    public function get_directory(): Directory
    {
        return $this->directory;
    }

    protected function get_file_extension(): string
    {
        return 'php';
    }

    protected function find_all_from_source(): ListScreenCollection
    {
        if (null === $this->list_screens) {
            $this->parse_directory();
        }

        return $this->list_screens;
    }

    public function get_sources(): SourceCollection
    {
        if (null === $this->sources) {
            $this->parse_directory();
        }

        return $this->sources;
    }

    private function parse_directory(): void
    {
        $this->list_screens = new ListScreenCollection();
        $this->sources = new SourceCollection();

        if ( ! $this->directory->is_readable()) {
            return;
        }

        $iterator = new Storage\FileIterator(
            new DirectoryIterator($this->directory->get_path()),
            $this->get_file_extension()
        );

        foreach ($iterator as $file) {
            $encoded_screen = require($file->getRealPath());

            try {
                $decoder = $this->decoder_factory->create($encoded_screen);
            } catch (DecoderNotFoundException $e) {
                continue;
            }

            if ( ! $decoder->has_list_screen()) {
                continue;
            }

            $list_screen = $decoder->get_list_screen();

            $this->segment_handler->load($list_screen);
            $this->conditional_format_handler->load($list_screen);

            $this->list_screens->add($list_screen);
            $this->sources->add($list_screen->get_id(), $file->getRealPath());
        }
    }

}