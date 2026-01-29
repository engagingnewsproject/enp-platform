<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC;
use AC\Exception\FailedToSaveListScreen;
use AC\ListScreen;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\TableScreenFactory;
use ACP\Exception\FailedToSaveConditionalFormattingException;
use ACP\Exception\FailedToSaveSegmentException;
use ACP\Storage\EncoderFactory;

final class Database extends AC\ListScreenRepository\Database
{

    private SegmentHandler $segment_handler;

    private ConditionalFormatHandler $conditional_format_handler;

    public function __construct(
        TableScreenFactory $table_screen_factory,
        EncoderFactory $encoder_factory,
        SegmentHandler $segment_handler,
        ConditionalFormatHandler $conditional_format_handler,
        AC\ColumnFactories\Aggregate $column_factory,
        OriginalColumnsRepository $original_column_repository
    ) {
        $this->segment_handler = $segment_handler;
        $this->conditional_format_handler = $conditional_format_handler;

        parent::__construct($table_screen_factory, $encoder_factory, $column_factory, $original_column_repository);
    }

    protected function create_list_screen(object $data): ?ListScreen
    {
        $list_screen = parent::create_list_screen($data);

        if ($list_screen) {
            $this->segment_handler->load($list_screen);
            $this->conditional_format_handler->load($list_screen);
        }

        return $list_screen;
    }

    /**
     * @throws FailedToSaveSegmentException
     * @throws FailedToSaveConditionalFormattingException
     * @throws FailedToSaveListScreen
     */
    public function save(ListScreen $list_screen): void
    {
        parent::save($list_screen);

        $this->segment_handler->save($list_screen);
        $this->conditional_format_handler->save($list_screen);
    }

    public function delete(ListScreen $list_screen): void
    {
        $this->segment_handler->delete($list_screen);
        $this->conditional_format_handler->delete($list_screen);

        parent::delete($list_screen);
    }

}