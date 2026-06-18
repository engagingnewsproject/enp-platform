<?php

declare(strict_types=1);

namespace ACA\ACF\Formatter\Media;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\ACF\Helper\Media\PostsContainingImageInAcfFinder;
use ACP\Helper\Media\MediaModalDefaults;

class PostsContainingImageInAcfCollection implements AC\Formatter
{

    /**
     * @var string[]
     */
    private array $post_statuses;

    private PostsContainingImageInAcfFinder $finder;

    /**
     * @param string[] $post_statuses
     */
    public function __construct(
        PostsContainingImageInAcfFinder $finder,
        array $post_statuses = MediaModalDefaults::POST_STATUSES
    ) {
        $this->finder = $finder;
        $this->post_statuses = $post_statuses;
    }

    public function format(Value $value)
    {
        $results = $this->finder->find((int)$value->get_id(), $this->post_statuses);

        return ValueCollection::from_ids($value->get_id(), array_keys($results));
    }

}
