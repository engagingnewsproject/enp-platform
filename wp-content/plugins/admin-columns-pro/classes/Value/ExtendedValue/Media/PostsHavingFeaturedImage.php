<?php

declare(strict_types=1);

namespace ACP\Value\ExtendedValue\Media;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use ACP\Helper\Media\MediaModalDefaults;
use ACP\Helper\Media\PostsHavingFeaturedImageFinder;
use ACP\Helper\Media\PostsModalRenderer;

class PostsHavingFeaturedImage implements ExtendedValue
{

    private const NAME = 'posts-having-featured-image';

    private PostsHavingFeaturedImageFinder $finder;

    private PostsModalRenderer $renderer;

    public function __construct(PostsHavingFeaturedImageFinder $finder, PostsModalRenderer $renderer)
    {
        $this->finder = $finder;
        $this->renderer = $renderer;
    }

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME, ['class' => '-w-xlarge']);
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $attachment_id = (int)$id;

        $post_ids = $this->finder->find($attachment_id, MediaModalDefaults::POST_STATUSES);

        if ([] === $post_ids) {
            return __('No items', 'codepress-admin-columns');
        }

        $total = count($post_ids);

        $title = sprintf(
            _n(
                '%d post uses this as featured image',
                '%d posts use this as featured image',
                $total,
                'codepress-admin-columns'
            ),
            $total
        );

        return $this->renderer->render($attachment_id, $post_ids, $title, MediaModalDefaults::POST_STATUSES);
    }

}
