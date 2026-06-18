<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use AC\ListScreenRepositoryWritable;
use AC\OpCacheInvalidateTrait;
use ACP\Exception\DirectoryNotWritableException;
use ACP\Exception\FailedToCreateDirectoryException;
use ACP\Exception\FailedToSaveConditionalFormattingException;
use ACP\Exception\FailedToSaveSegmentException;
use ACP\Exception\FileNotWritableException;
use ACP\Storage;
use ACP\Storage\Directory;
use ACP\Storage\EncodedContext;
use ACP\Storage\EncoderFactory;
use ACP\Storage\Serializer;
use DirectoryIterator;
use Generator;

final class File extends SourceAwareEncoded implements ListScreenRepositoryWritable, DirectoryAware
{

    use OpCacheInvalidateTrait;

    private Directory $directory;

    private EncoderFactory $encoder_factory;

    private Serializer $serializer;

    private SegmentHandler $segment_handler;

    private ConditionalFormatHandler $conditional_format_handler;

    public function __construct(
        Directory $directory,
        Storage\CompositeDecoderFactory $decoder_factory,
        EncoderFactory $encoder_factory,
        Serializer $serializer,
        SegmentHandler $segment_handler,
        ConditionalFormatHandler $conditional_format_handler
    ) {
        parent::__construct($decoder_factory);

        $this->directory = $directory;
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

        // Reflect the new situation
        $this->check_decoders(self::UPDATE_CACHE);
    }

    /**
     * @throws FileNotWritableException
     */
    public function delete(ListScreen $list_screen): void
    {
        $this->check_decoders();

        $id = $list_screen->get_id();

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

        // Reflect the new situation
        $this->check_decoders(self::UPDATE_CACHE);
    }

    public function get_directory(): Directory
    {
        return $this->directory;
    }

    protected function get_file_extension(): string
    {
        return 'php';
    }

    protected function create_list_screen(Storage\Decoder $decoder): ListScreen
    {
        $list_screen = parent::create_list_screen($decoder);

        $this->segment_handler->load($list_screen);
        $this->conditional_format_handler->load($list_screen);

        return $list_screen;
    }

    protected function get_encoded_contexts(): Generator
    {
        if ( ! $this->directory->is_readable()) {
            return;
        }

        $iterator = new Storage\FileIterator(
            new DirectoryIterator($this->directory->get_path()),
            $this->get_file_extension()
        );

        foreach ($iterator as $file) {
            $encoded_screen = require $file->getRealPath();

            yield (new EncodedContext($encoded_screen))
                ->with_attribute(self::SOURCE_ATTRIBUTE, $file->getRealPath());
        }
    }

}