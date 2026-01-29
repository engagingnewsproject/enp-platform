<?php

declare(strict_types=1);

namespace ACA\MLA\Export\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use WP_Post;
use WP_Post_Type;

class AttachedTo implements Formatter
{

    use ExtendedPostTrait;

    private function format_post_status(string $post_status): ?string
    {
        switch ($post_status) {
            case 'draft' :
                return __('Draft');
            case 'future' :
                return __('Scheduled');
            case 'pending' :
                return _x('Pending', 'post state');
            case 'trash' :
                return __('Trash');
            default:
                return null;
        }
    }

    private function user_can_read_parent(WP_Post $post): bool
    {
        $post_type = get_post_type_object($post->parent_type ?? '');

        if ( ! $post_type instanceof WP_Post_Type) {
            return false;
        }

        return ! $post_type->show_ui || current_user_can($post_type->cap->read_post, $post->post_parent);
    }

    public function format(Value $value)
    {
        $id = $value->get_id();
        $post = $this->get_extended_post((int)$id);

        if ( ! $post) {
            throw ValueNotFoundException::from_id($id);
        }

        $parent_title = $post->parent_title ?? null;

        if ($post && $parent_title) {
            $parent_type = $post->parent_type ?? '';
            $parent_date = $post->parent_date ?? '';
            $parent_status = $post->parent_status ?? '';

            $user_can_read_parent = $this->user_can_read_parent($post);

            $_value = $user_can_read_parent
                ? esc_attr($parent_title)
                : __('(Private post)');

            if ($parent_date && $user_can_read_parent) {
                $_value .= "\n" . mysql2date(__('Y/m/d', 'media-library-assistant'), $parent_date);
            }

            if ($parent_type && $user_can_read_parent) {
                $_parent = $post->post_parent;
                $_status = $this->format_post_status($parent_status);

                if ($_status) {
                    $_parent = sprintf('%s, %s', $_parent, $_status);
                }

                $_value .= sprintf("\n(%s %s)", $parent_type, $_parent);
            }

            return $value->with_value($_value);
        }

        return $value->with_value(
            sprintf('(%s)', _x('Unattached', 'table_view_singular', 'media-library-assistant'))
        );
    }

}