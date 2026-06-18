<?php
/**
 * @var AC\View $this
 */

/** @var WC_Order_Item_Product[] $items */
$items = $this->get('items');

if (empty($items)) {
    return;
}

foreach ($items as $item):
    $product = $item->get_product();
    if ( ! $product instanceof WC_Product) {
        continue;
    }
    ?>

	<div class="acu-mb-2">
		<div>
			<span class="qty"><?= $item->get_quantity() ?>x</span>
			<strong><?= $product->get_name() ?></strong>


            <?php
            if (wc_product_sku_enabled() && $product->get_sku()): ?>
				<small class="sku acu-font-mono">(<?= $product->get_sku() ?>)</small>
            <?php
            endif ?>
		</div>
        <?php
        $meta = $item->get_formatted_meta_data('_', true);
        ?>
        <?php
        if ( ! empty($meta)) : ?>
			<div class="ac-wc-meta acu-font-mono acu-text-[11px] acu-leading-3">
                <?php
                foreach ($meta as $meta_item): ?>
					<div style="line-height:150%">
						<strong><?= $meta_item->display_key ?></strong>
                        <?= strip_tags($meta_item->display_value) ?>
					</div>
                <?php
                endforeach ?>
			</div>

        <?php
        endif; ?>
	</div>


<?php
endforeach; ?>