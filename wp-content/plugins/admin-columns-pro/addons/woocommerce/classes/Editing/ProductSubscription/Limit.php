<?php

declare(strict_types=1);

namespace ACA\WC\Editing\ProductSubscription;

use ACA\WC\Editing\EditValue;
use ACA\WC\Editing\Product\ProductNotSupportedReasonTrait;
use ACA\WC\Editing\StorageModel;
use ACP;
use ACP\Editing\View;

class Limit implements ACP\Editing\Service, ACP\Editing\Service\Editability
{

    use ProductSubscriptionEditableTrait;
    use ProductNotSupportedReasonTrait;

    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function get_view(string $context): ?View
    {
        return new ACP\Editing\View\Select($this->options);
    }

    public function get_value(int $id)
    {
        $product = wc_get_product($id);

        return $product
            ? $product->get_meta('_subscription_limit')
            : false;
    }

    public function update(int $id, $data): void
    {
        update_post_meta($id, '_subscription_limit', $data);
    }

}