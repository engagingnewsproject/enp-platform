<?php

declare(strict_types=1);

namespace ACP\Tools;

use AC\ListScreen;
use AC\ListScreenCollection;
use AC\ListScreenRepository\Storage;
use AC\Storage\Repository\ListScreenOrder;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;
use ACP\ListScreenFactory;
use ACP\Storage\AbstractDecoderFactory;
use Exception;

class ImportHandler
{

    private Storage $storage;

    private AbstractDecoderFactory $decoder_factory;

    private ListScreenOrder $order_storage;

    private ListScreenFactory $list_screen_factory;

    public function __construct(
        Storage $storage,
        AbstractDecoderFactory $decoder_factory,
        ListScreenOrder $order_storage,
        ListScreenFactory $list_screen_factory
    ) {
        $this->storage = $storage;
        $this->decoder_factory = $decoder_factory;
        $this->order_storage = $order_storage;
        $this->list_screen_factory = $list_screen_factory;
    }

    public function handle(array $encoded_data, ?ListScreenId $id = null, array $overwrites = []): ListScreenCollection
    {
        $list_screens = new ListScreenCollection();

        $overwrites = array_merge([
            'title'  => '',
            'status' => '',
        ], $overwrites);

        foreach ($encoded_data as $encoded_item) {
            $decoder = $this->decoder_factory->create($encoded_item);

            if ( ! $decoder->has_list_screen()) {
                continue;
            }

            $list_screen_source = $decoder->get_list_screen();

            if ($id && ! $list_screen_source->get_id()->equals($id)) {
                continue;
            }

            $list_screen = $this->list_screen_factory->duplicate($list_screen_source);
            $list_screen->set_title($this->get_title($list_screen));

            if ($overwrites['title'] && is_string($overwrites['title'])) {
                $list_screen->set_title($overwrites['title']);
            }
            if ($overwrites['status'] instanceof ListScreenStatus) {
                $list_screen->set_status($overwrites['status']);
            }
            if ($overwrites['id'] instanceof ListScreenId) {
                $list_screen->set_id($overwrites['id']);
            }

            try {
                $this->storage->save($list_screen);
            } catch (Exception $e) {
                continue;
            }

            $this->order_storage->add(
                $list_screen->get_table_id(),
                $list_screen->get_id()
            );

            $list_screens->add($list_screen);
        }

        return $list_screens;
    }

    private function get_title(ListScreen $list_screen): string
    {
        $title = $list_screen->get_title();

        if ($this->exists_title($list_screen->get_table_id(), $title)) {
            $title = sprintf('%s (%s)', $title, __('copy', 'codepress-admin-columns'));
        }

        return $title;
    }

    private function exists_title(TableId $table_id, string $title): bool
    {
        foreach ($this->storage->find_all_by_table_id($table_id) as $list_screen) {
            if ($list_screen->get_title() === $title) {
                return true;
            }
        }

        return false;
    }

}