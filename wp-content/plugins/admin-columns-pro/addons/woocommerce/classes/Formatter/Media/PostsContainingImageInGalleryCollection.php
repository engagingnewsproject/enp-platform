<?php

declare(strict_types=1);

namespace ACA\WC\Formatter\Media;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\WC\Helper\Media\PostsContainingImageInGalleryFinder;
use ACP\Helper\Media\MediaModalDefaults;

class PostsContainingImageInGalleryCollection implements AC\Formatter
{

    /**
     * @var string[]
     */
    private array $post_statuses;

    private PostsContainingImageInGalleryFinder $finder;

    /**
     * @param string[] $post_statuses
     */
    public function __construct(
        PostsContainingImageInGalleryFinder $finder,
        array $post_statuses = MediaModalDefaults::POST_STATUSES
    ) {
        $this->finder = $finder;
        $this->post_statuses = $post_statuses;
    }

    public function format(Value $value)
    {
        $post_ids = $this->finder->find((int)$value->get_id(), $this->post_statuses);

        return ValueCollection::from_ids($value->get_id(), $post_ids);
    }

}
