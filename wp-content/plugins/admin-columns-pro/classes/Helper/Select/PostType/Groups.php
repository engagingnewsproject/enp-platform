<?php

declare(strict_types=1);

namespace ACP\Helper\Select\PostType;

use AC\Helper\Select;
use AC\Helper\Select\OptionGroup;

class Groups extends Select\Options
{

    public function __construct(Options $options, GroupFormatter $formatter, ?string $order = null)
    {
        parent::__construct(
            $this->create_groups(
                $options,
                $formatter,
                $order === 'desc'
                    ? 'desc'
                    : 'asc'
            )
        );
    }

    private function create_groups(Options $options, GroupFormatter $formatter, string $order): array
    {
        $groups = [];

        foreach ($options as $option) {
            $post_type = $options->get_post_type((string)$option->get_value());

            $groups[$formatter->format($post_type)][] = $option;
        }

        uksort($groups, function ($a, $b) use ($order) {
            return 'desc' === $order
                ? strnatcmp($b, $a)
                : strnatcmp($a, $b);
        });

        $option_groups = [];

        foreach ($groups as $label => $_options) {
            $option_groups[] = new OptionGroup($label, $_options);
        }

        return $option_groups;
    }

}