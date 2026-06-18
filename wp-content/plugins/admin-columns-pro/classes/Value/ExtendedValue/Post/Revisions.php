<?php

declare(strict_types=1);

namespace ACP\Value\ExtendedValue\Post;

use AC\Column;
use AC\ListScreen;
use AC\Value\Extended\ExtendedValue;
use AC\Value\ExtendedValueLink;
use AC\View;

class Revisions implements ExtendedValue
{

    private const NAME = 'post-revisions';

    public function can_render(string $view): bool
    {
        return $view === self::NAME;
    }

    public function get_link($id, string $label): ExtendedValueLink
    {
        return new ExtendedValueLink($label, $id, self::NAME);
    }

    public function render($id, array $params, Column $column, ListScreen $list_screen): string
    {
        $count = count(wp_get_post_revisions($id, ['posts_per_page' => -1, 'fields' => 'ids']));
        $revision = wp_get_post_revisions($id, ['posts_per_page' => 30]);

        $view = new View([
            'title'     => sprintf(_n('%d revision', '%d revisions', $count, 'codepress-admin-columns'), $count),
            'revisions' => $revision,
        ]);

        return $view->set_template('modal-value/revisions')->render();
    }

}