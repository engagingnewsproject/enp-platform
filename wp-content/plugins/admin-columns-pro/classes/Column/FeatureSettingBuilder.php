<?php

declare(strict_types=1);

namespace ACP\Column;

use AC\Setting;
use ACP\Editing\Setting\ComponentFactory\BulkEdit;
use ACP\Editing\Setting\ComponentFactory\InlineEdit;
use ACP\Export\Setting\ComponentFactory\Export;
use ACP\Filtering\Setting\ComponentFactory\Filtering;
use ACP\Search\Setting\ComponentFactory\Search;
use ACP\Sorting\Setting\ComponentFactory\Sort;

final class FeatureSettingBuilder
{

    private ?InlineEdit $edit = null;

    private InlineEdit $edit_default;

    private ?BulkEdit $bulk_edit = null;

    private BulkEdit $bulk_edit_default;

    private ?Export $export = null;

    private Export $export_default;

    private ?Filtering $filter = null;

    private Filtering $filter_default;

    private ?Search $search = null;

    private Search $search_default;

    private ?Sort $sort = null;

    private Sort $sort_default;

    public function __construct(
        InlineEdit $edit,
        BulkEdit $bulk_edit,
        Export $export,
        Filtering $filter,
        Search $search,
        Sort $sort
    ) {
        $this->edit_default = $edit;
        $this->bulk_edit_default = $bulk_edit;
        $this->export_default = $export;
        $this->filter_default = $filter;
        $this->search_default = $search;
        $this->sort_default = $sort;
    }

    public function set_edit(?InlineEdit $edit = null, ?BulkEdit $bulk_edit = null): self
    {
        if ($edit === null) {
            $edit = $this->edit_default;
        }

        if ($bulk_edit === null) {
            $bulk_edit = $this->bulk_edit_default;
        }

        $this->edit = $edit;
        $this->bulk_edit = $bulk_edit;

        return $this;
    }

    public function set_bulk_edit(?BulkEdit $bulk_edit = null): self
    {
        $this->bulk_edit = $bulk_edit;

        return $this;
    }

    public function set_export(?Export $export = null): self
    {
        if ($export === null) {
            $export = $this->export_default;
        }

        $this->export = $export;

        return $this;
    }

    public function set_search(?Search $search = null, ?Filtering $filtering = null): self
    {
        if ($search === null) {
            $search = $this->search_default;
        }

        if ($filtering === null) {
            $filtering = $this->filter_default;
        }

        $this->filter = $filtering;
        $this->search = $search;

        return $this;
    }

    public function set_sort(?Sort $sort = null): self
    {
        if ($sort === null) {
            $sort = $this->sort_default;
        }

        $this->sort = $sort;

        return $this;
    }

    public function build(Setting\Config $config): Setting\ComponentCollection
    {
        $settings = [];

        if ($this->edit) {
            $settings[] = $this->edit->create($config);
        }

        if ($this->bulk_edit) {
            $settings[] = $this->bulk_edit->create($config);
        }

        if ($this->export) {
            $settings[] = $this->export->create($config);
        }

        if ($this->search) {
            $settings[] = $this->search->create($config);
            $settings[] = $this->filter->create($config);
        }

        if ($this->sort) {
            $settings[] = $this->sort->create($config);
        }

        return new Setting\ComponentCollection($settings, 20);
    }

}