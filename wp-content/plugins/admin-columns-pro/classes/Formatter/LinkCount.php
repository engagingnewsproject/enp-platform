<?php

declare(strict_types=1);

namespace ACP\Formatter;

use AC;
use AC\Type\Value;
use AC\Type\ValueCollection;

class LinkCount implements AC\CollectionFormatter
{

    private bool $add_tooltip;

    public function __construct(bool $add_tooltip = false)
    {
        $this->add_tooltip = $add_tooltip;
    }

    public function format(ValueCollection $collection): Value
    {
        $urls = iterator_to_array($collection);

        if (empty($urls)) {
            throw AC\Exception\ValueNotFoundException::from_id($collection->get_id());
        }

        $value = (string)count($urls);

        if ($this->add_tooltip) {
            $value = ac_helper()->html->tooltip(
                $value,
                implode('<br>', array_map([$this, 'trim_tooltip_url'], $urls))
            );
        }

        return new Value(
            $collection->get_id(),
            $value
        );
    }

    private function remove_home_url_prefix(string $url): string
    {
        return str_replace(home_url(), '', $url);
    }

    private function trim_tooltip_url(string $url): string
    {
        return ac_helper()->string->trim_characters($url, 26);
    }

}