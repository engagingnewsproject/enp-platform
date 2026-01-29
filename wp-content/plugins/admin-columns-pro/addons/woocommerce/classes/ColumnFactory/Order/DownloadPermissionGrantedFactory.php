<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DownloadPermissionGrantedFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Download Permission Granted', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_download_permissions_granted';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        // TODO test
        return parent::get_formatters($config)
                     ->add(new Formatter\Order\DownloadPermissionGranted())
                     ->add(
                         new AC\Formatter\YesIcon(
                             __('Download Permission Granted', 'codepress-admin-columns')
                         )
                     );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OperationalData('download_permission_granted');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\DownloadPermissionGranted();
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\DownloadPermissionGranted());
    }

}