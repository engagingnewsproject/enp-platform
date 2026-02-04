<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\RulesRepository;

use AC\OpCacheInvalidateTrait;
use AC\Type\ListScreenId;
use ACP\ConditionalFormat\Decoder;
use ACP\ConditionalFormat\Encoder;
use ACP\ConditionalFormat\Entity\Rules;
use ACP\ConditionalFormat\RulesCollection;
use ACP\ConditionalFormat\RulesRepository;
use ACP\ConditionalFormat\Type\Key;
use ACP\Exception\DirectoryNotWritableException;
use ACP\Exception\FailedToCreateDirectoryException;
use ACP\Exception\FailedToSaveConditionalFormattingException;
use ACP\Exception\FileNotWritableException;
use ACP\Storage\Directory;
use ACP\Storage\Serializer;
use DirectoryIterator;
use SplFileInfo;

class File extends RulesRepository
{

    private const SUFFIX = 'conditional_format';
    private const EXTENSION = 'php';

    use OpCacheInvalidateTrait;

    private Encoder $encoder;

    private Decoder $decoder;

    private Database $personal_rules_repository;

    private Directory $directory;

    private Serializer $serializer;

    public function __construct(
        Encoder $encoder,
        Decoder $decoder,
        Database $personal_rules_repository,
        Directory $directory,
        Serializer $serializer
    ) {
        $this->encoder = $encoder;
        $this->decoder = $decoder;
        $this->personal_rules_repository = $personal_rules_repository;
        $this->directory = $directory;
        $this->serializer = $serializer;
    }

    // Retrieval

    public function find_all_shared(ListScreenId $list_screen_id): RulesCollection
    {
        return $this->fetch_results($list_screen_id);
    }

    public function find_all_personal(ListScreenId $list_screen_id, int $user_id): RulesCollection
    {
        return $this->personal_rules_repository->find_all_personal($list_screen_id, $user_id);
    }

    // Save

    /**
     * @throws FileNotWritableException
     * @throws DirectoryNotWritableException
     * @throws FailedToCreateDirectoryException
     * @throws FailedToSaveConditionalFormattingException
     */
    public function save(Rules $rules): void
    {
        // Personal is saved in the database always
        if ($rules->has_user_id()) {
            $this->personal_rules_repository->save($rules);

            return;
        }

        if ( ! $this->directory->exists()) {
            $this->directory->create();
        }

        if ( ! $this->directory->is_writable()) {
            throw new DirectoryNotWritableException($this->directory->get_path());
        }

        $file = $this->get_file_name($rules->get_list_id());

        // If we get more decoders, we need to make sure we get data according to the latest encoder
        $encoded = $this->get_encoded_data($file);
        $encoded[(string)$rules->get_key()] = $this->encoder->encode_rules($rules);

        $this->write_file($file, $encoded);
    }

    // Delete

    /**
     * @throws FileNotWritableException
     */
    public function delete(ListScreenId $list_screen_id, Key $key): void
    {
        if ($this->personal_rules_repository->find($list_screen_id, $key)) {
            $this->personal_rules_repository->delete($list_screen_id, $key);
        }

        $rules = $this->find($list_screen_id, $key);

        // Already deleted or does not exist
        if ( ! $rules) {
            return;
        }

        $file = $this->get_file_name($list_screen_id);
        $encoded = $this->get_encoded_data($file);

        $rules = $encoded[(string)$key] ?? null;

        // Already deleted or does not exist
        if ( ! $rules) {
            return;
        }

        unset($encoded[(string)$key]);

        if (count($encoded)) {
            $this->write_file($file, $encoded);
        } else {
            $this->delete_file($file);
        }
    }

    /**
     * @throws FileNotWritableException
     */
    public function delete_all(ListScreenId $list_screen_id): void
    {
        $this->delete_all_shared($list_screen_id);
        $this->personal_rules_repository->delete_all($list_screen_id);
    }

    public function delete_all_personal(ListScreenId $list_screen_id, int $user_id): void
    {
        $this->personal_rules_repository->delete_all_personal($list_screen_id, $user_id);
    }

    /**
     * @throws FileNotWritableException
     */
    public function delete_all_shared(ListScreenId $list_screen_id): void
    {
        $this->delete_file($this->get_file_name($list_screen_id));
    }

    // Helpers

    private function get_file_name(ListScreenId $list_screen_id): string
    {
        return sprintf(
            '%s/%s_%s.%s',
            $this->directory->get_path(),
            $list_screen_id,
            self::SUFFIX,
            self::EXTENSION
        );
    }

    /**
     * @throws FileNotWritableException
     */
    private function write_file(string $file, array $encoded_data): void
    {
        $result = file_put_contents(
            $file,
            $this->serializer->serialize($encoded_data)
        );

        if ($result === false) {
            throw FileNotWritableException::for_file($file);
        }

        $this->opcache_invalidate($file);
    }

    /**
     * @throws FileNotWritableException
     */
    private function delete_file(string $file): void
    {
        $this->opcache_invalidate($file);

        if ( ! file_exists($file)) {
            return;
        }

        $result = unlink($file);

        if ($result === false) {
            throw FileNotWritableException::for_file($file);
        }
    }

    private function get_encoded_data(string $file): array
    {
        $info = new SplFileInfo($file);

        return $info->isFile()
            ? require $info->getRealPath()
            : [];
    }

    protected function fetch_results(
        ListScreenId $list_screen_id,
        ?Key $key = null
    ): RulesCollection {
        $result = new RulesCollection();

        if ( ! $this->directory->is_readable()) {
            return $result;
        }

        foreach ((new DirectoryIterator($this->directory->get_path())) as $file) {
            if ( ! $file->isReadable() ||
                 ! $file->isFile() ||
                 ! $file->getSize() ||
                 $file->getExtension() !== self::EXTENSION ||
                 ! str_contains($file->getBasename(), self::SUFFIX)
            ) {
                continue;
            }

            if ($list_screen_id && ! str_contains($file->getBasename(), (string)$list_screen_id)) {
                continue;
            }

            $encoded = $this->get_encoded_data($file->getRealPath());

            foreach ($encoded as $k => $v) {
                // If a key is supplied, only check for that item
                if ($key && $k !== (string)$key) {
                    continue;
                }

                $result->add($this->decoder->decode($v));
            }
        }

        return $result;
    }

}