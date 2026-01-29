<?php

declare(strict_types=1);

namespace ACP\ColumnFactory\User;

use AC\Formatter\User\Meta;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;

class Language extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    public function get_column_type(): string
    {
        return 'column-user_default_language';
    }

    public function get_label(): string
    {
        return __('Language');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new Meta('locale'),
            new ACP\Formatter\LanguageNativeName(),
        ]);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new Meta('locale'),
            new ACP\Export\Formatter\User\Language(),
        ]);
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\User\LanguageRemote();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Comparison\User\Languages();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta('locale');
    }

}