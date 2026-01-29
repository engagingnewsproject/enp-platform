<?php

declare(strict_types=1);

namespace ACA\WC\Editing;

use AC\Helper\Select\Options\Paginated;
use ACA\WC\Helper\Select\Product\PaginatedFactory;
use ACP;
use ACP\Editing\PaginatedOptions;
use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;
use InvalidArgumentException;

abstract class ProductRelations implements Service, PaginatedOptions
{

    use PostTrait;

    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function get_value(int $id): array
    {
        return $this->get_editable_posts_values($this->get_relation_ids($id));
    }

    private function get_relation_ids($id): array
    {
        $ids = $this->storage->get($id);

        return $ids && is_array($ids)
            ? $ids
            : [];
    }

    private function sanitize_ids(array $ids): array
    {
        return $ids
            ? array_map('intval', array_filter($ids, 'is_numeric'))
            : [];
    }

    public function update(int $id, $data): void
    {
        $method = $data['method'] ?? null;

        if ( ! $method) {
            $this->storage->update($id, $this->sanitize_ids((array)$data));

            return;
        }

        $relation_ids = $data['value'] ?? [];

        if ( ! is_array($relation_ids)) {
            throw new InvalidArgumentException('Invalid value');
        }

        $relation_ids = $this->sanitize_ids($relation_ids);

        switch ($method) {
            case 'add':
                $this->storage->update($id, array_merge($this->get_relation_ids($id), $relation_ids));

                break;
            case 'remove':
                $this->storage->update($id, array_diff($this->get_relation_ids($id), $relation_ids));

                break;
            default:
                $this->storage->update($id, $relation_ids);
        }
    }

    public function get_view(string $context): ?View
    {
        $view = (new ACP\Editing\View\AjaxSelect())
            ->set_multiple(true)
            ->set_clear_button(true);

        if ($context === self::CONTEXT_BULK) {
            $view->has_methods(true)->set_revisioning(false);
        }

        return $view;
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            's'     => $search,
            'paged' => $page,
        ]);
    }

}