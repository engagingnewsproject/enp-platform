<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Helper\Media\MediaModalDefaults;
use ACP\Helper\Media\PostsContainingImageFinder;

class PostsContainingImageInContentCollection implements AC\Formatter
{

    /**
     * @var string[]
     */
    private array $post_statuses;

    private PostsContainingImageFinder $finder;

    /**
     * @param string[] $post_statuses
     */
    public function __construct(
        PostsContainingImageFinder $finder,
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
