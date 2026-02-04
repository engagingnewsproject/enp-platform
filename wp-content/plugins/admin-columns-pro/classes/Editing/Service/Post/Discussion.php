<?php

namespace ACP\Editing\Service\Post;

use AC\Type\ToggleOptions;
use ACP\Editing\Service;
use ACP\Editing\View;

class Discussion implements Service
{

    public function get_view(string $context): ?View
    {
        $view = new View\Composite('vertical');
        $view->add_field(
            new View\CompositeField\SelectField(
                'comment_status',
                __(
                    'Comment Status',
                    'codepress-admin-columns'
                ),
                [
                    'open'   => _x('Open', 'Comment Status', 'codepress-admin-columns'),
                    'closed' => _x('Closed', 'Comment Status', 'codepress-admin-columns'),
                ]
            )
        );

        $view->add_field(
            new View\CompositeField\CheckboxField(
                'ping_status',
                __('Enable pingbacks & trackbacks', 'codepress-admin-columns'),
                ToggleOptions::create_from_array([
                    'closed' => 'Closed',
                    'open'   => 'Open',
                ])
            )
        );

        return $view;
    }

    public function get_value(int $id)
    {
        $post = get_post($id);

        return [
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
        ];
    }

    public function update(int $id, $data): void
    {
        wp_update_post([
            'ID'             => $id,
            'comment_status' => $data['comment_status'],
            'ping_status'    => $data['ping_status'],
        ]);
    }
}