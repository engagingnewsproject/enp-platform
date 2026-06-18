<?php

declare(strict_types=1);

namespace ACA\JetEngine\Editing\Service;

use AC\Helper\Select\Options\Paginated;
use ACA\JetEngine\Utils\Api;
use ACP;
use ACP\Helper\Select\Post\PaginatedFactory;

class RelationshipLegacy implements ACP\Editing\Service, ACP\Editing\PaginatedOptions
{

    private string $related_key;

    private string $current_post_type;

    private string $related_post_type;

    private bool $multiple;

    public function __construct(
        string $related_key,
        string $current_post_type,
        string $related_post_type,
        bool $multiple
    ) {
        $this->related_key = $related_key;
        $this->current_post_type = $current_post_type;
        $this->related_post_type = $related_post_type;
        $this->multiple = $multiple;
    }

    public function get_view(string $context): ACP\Editing\View\AjaxSelect
    {
        return (new ACP\Editing\View\AjaxSelect())->set_multiple($this->multiple);
    }

    public function get_value(int $id)
    {
        $post_ids = Api::relations()->get_related_posts([
            'hash'    => $this->related_key,
            'current' => $this->current_post_type,
            'post_id' => $id,
        ]);

        if (empty($post_ids)) {
            return [];
        }

        $result = [];

        foreach ((array)$post_ids as $post_id) {
            $result[$post_id] = get_the_title($post_id);
        }

        return $result;
    }

    public function update(int $id, $data): void
    {
        $ids = is_array($data) ? $data : [$data];

        Api::relations()->process_meta(true, $id, $this->related_key, $ids);
    }

    public function get_paginated_options(string $search, int $page, ?int $id = null): Paginated
    {
        return (new PaginatedFactory())->create([
            's'         => $search,
            'paged'     => $page,
            'post_type' => $this->related_post_type,
        ]);
    }

}