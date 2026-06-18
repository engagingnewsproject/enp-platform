<?php

declare(strict_types=1);

namespace ACA\Polylang\ColumnFactory;

use AC\Column\BaseColumnFactory;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\Message;
use AC\Setting\Config;

class Language extends BaseColumnFactory
{

    public const COLUMN_TYPE = 'polylang_flag_placeholder';

    protected function get_settings(Config $config): ComponentCollection
    {
        $message = new Message(
            __('Instructions', 'codepress-admin-columns'),
            __(
                'This placeholder columns adds the Polylang language flags columns to the list page.',
                'codepress-admin-column'
            )
        );

        return new ComponentCollection([
            $message->create($config),
        ]);
    }

    public function get_label(): string
    {
        return __('Polylang Language', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return self::COLUMN_TYPE;
    }

    protected function get_group(): ?string
    {
        return 'polylang';
    }

}