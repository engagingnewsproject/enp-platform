<?php

declare(strict_types=1);

namespace ACP\Formatter\Media;

use AC;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;
use AC\Value\Extended\ExtendedValue;
use Closure;

class PostsContainingImageExtendedLink implements AC\CollectionFormatter
{

    private ExtendedValue $extended_value;

    private Closure $label_builder;

    /**
     * @param callable|null $label_builder fn(int $count): string — defaults to "%d post(s)"
     */
    public function __construct(ExtendedValue $extended_value, ?callable $label_builder = null)
    {
        $this->extended_value = $extended_value;
        $this->label_builder = $label_builder
            ? Closure::fromCallable($label_builder)
            : fn(int $count): string => sprintf(
                _n('%d post', '%d posts', $count, 'codepress-admin-columns'),
                $count
            );
    }

    public function format(ValueCollection $value): Value
    {
        if ($value->count() === 0) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $count = $value->count();
        $id = $value->get_id();

        $label = ($this->label_builder)($count);

        $link = $this->extended_value
            ->get_link($id, $label)
            ->with_edit_link((string)get_edit_post_link($id))
            ->with_title(strip_tags(get_the_title($id)) ?: (string)$id);

        return new Value($id, $link->render());
    }

}
