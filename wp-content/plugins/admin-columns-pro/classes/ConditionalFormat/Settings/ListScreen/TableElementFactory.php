<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Settings\ListScreen;

use ACP;
use ACP\Settings;
use ACP\Settings\ListScreen\TableElement;

final class TableElementFactory implements Settings\ListScreen\TableElementFactory
{

    public function create(): ACP\Settings\ListScreen\TableElement
    {
        return new TableElement(
            'hide_conditional_formatting',
            __('Conditional Formatting', 'codepress-admin-columns'),
            'feature'
        );
    }

}