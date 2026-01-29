<?php

namespace ACP\Editing;

use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Service\ManageValue;
use AC\Storage\Repository\OriginalColumnsRepository;
use ACP\Editing\Strategy\AggregateFactory;
use LogicException;

class RequestHandlerAjaxFactory
{

    private const METHOD_BULK_DELETE = 'bulk-delete';
    private const METHOD_BULK_SAVE = 'bulk-save';
    private const METHOD_INLINE_SAVE = 'inline-save';
    private const METHOD_INLINE_VALUES = 'inline-values';
    private const METHOD_EDIT_STATE = 'edit-state';
    private const METHOD_INLINE_SELECT_VALUES = 'inline-select-values';
    private const METHOD_BULK_SELECT_VALUES = 'bulk-select-values';
    private const METHOD_USER_SELECT_VALUES = 'delete-user-select-values';

    private Storage $storage;

    private AggregateFactory $aggregate_factory;

    private BulkDelete\AggregateFactory $aggregate_factory_delete;

    private OriginalColumnsRepository $original_columns_repository;

    private ManageValue $manage_value_service;

    public function __construct(
        Storage $storage,
        AggregateFactory $aggregate_factory,
        BulkDelete\AggregateFactory $aggregate_factory_delete,
        OriginalColumnsRepository $original_columns_repository,
        ManageValue $manage_value_service
    ) {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
        $this->aggregate_factory_delete = $aggregate_factory_delete;
        $this->original_columns_repository = $original_columns_repository;
        $this->manage_value_service = $manage_value_service;
    }

    public function create(Request $request): RequestHandler
    {
        switch ($request->get('method')) {
            case self::METHOD_BULK_DELETE :
                return new RequestHandler\BulkDelete($this->storage, $this->aggregate_factory_delete);
            case self::METHOD_BULK_SAVE :
                return new RequestHandler\BulkSave($this->storage, $this->aggregate_factory);
            case self::METHOD_INLINE_SAVE :
                return new RequestHandler\InlineSave(
                    $this->storage,
                    $this->aggregate_factory,
                    $this->original_columns_repository,
                    $this->manage_value_service
                );
            case self::METHOD_INLINE_VALUES :
                return new RequestHandler\InlineValues(
                    $this->storage, $this->aggregate_factory
                );
            case self::METHOD_EDIT_STATE :
                return new RequestHandler\EditState(new Preference\EditState());
            case self::METHOD_INLINE_SELECT_VALUES :
            case self::METHOD_BULK_SELECT_VALUES :
                return new RequestHandler\SelectValues($this->storage, $this->aggregate_factory);
            case self::METHOD_USER_SELECT_VALUES :
                return new RequestHandler\DeleteUserSelectValues();
        }

        throw new LogicException('Invalid request.');
    }

}