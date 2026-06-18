<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\SeoPress\Editing;
use ACP;

final class LoginStatus extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_seopress_redirections_logged_status';

    protected function get_group(): ?string
    {
        return 'seopress';
    }

    public function get_label(): string
    {
        return __('Login Status', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-sp_login_status';
    }

    private function get_login_statuses(): array
    {
        return [
            'both'               => __('All', 'wp-seopress'),
            'only_logged_in'     => __('Only Logged In', 'wp-seopress'),
            'only_not_logged_in' => __('Only Not Logged In', 'wp-seopress'),
        ];
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_login_statuses()),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(self::META_KEY, $this->get_login_statuses());
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\Post\Meta(self::META_KEY))
                     ->add(new AC\Formatter\MapOptionLabel($this->get_login_statuses()));
    }

}