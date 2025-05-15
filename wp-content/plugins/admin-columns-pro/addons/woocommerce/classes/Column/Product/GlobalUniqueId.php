<?php

namespace ACA\WC\Column\Product;

use AC;
use ACA\WC\Editing;
use ACP;

class GlobalUniqueId extends AC\Column\Meta
    implements ACP\Editing\Editable, ACP\Export\Exportable, ACP\Search\Searchable
{

    public function __construct()
    {
        $this->set_type('column-wc-product_global_uid')
             ->set_group('woocommerce')
             ->set_label(__('GTIN, UPC, EAN or ISBN.', 'woocommerce'));
    }

    public function get_meta_key(): string
    {
        return '_global_unique_id';
    }

    public function editing(): ACP\Editing\Service\Basic
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_clear_button(true),
            new Editing\Storage\Product\GlobalUniqueId()
        );
    }

    public function export()
    {
        return new ACP\Export\Model\Value($this);
    }

    public function search()
    {
        return new ACP\Search\Comparison\Meta\Text($this->get_meta_key());
    }

}